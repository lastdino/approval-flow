<?php

namespace Lastdino\ApprovalFlow\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Lastdino\ApprovalFlow\Exceptions\ConfigurationException;
use Lastdino\ApprovalFlow\Exceptions\InvalidNodeException;
use Lastdino\ApprovalFlow\Models\ApprovalFlowHistory;
use Lastdino\ApprovalFlow\Models\ApprovalFlowTask;
use Lastdino\ApprovalFlow\Notifications\ApprovalFlowNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ApprovalFlowService
{
    // 定数定義
    private const START_NODE_ID = 1;
    private const APPROVAL_STATUS = 'Approved';
    private const REJECTION_STATUS = 'Rejected';
    private const REQUEST_STATUS = 'Request';
    private const CANCELLED_STATUS = 'Cancelled';

    // キャッシュ用プロパティ
    private array $startHistoryCache = [];
    private array $inputNodesCache = [];
    private array $approvalCountCache = [];
    private array $rejectionCache = [];

    // ノードタイプ定数
    private const NODE_START = 'start';
    private const NODE_AND = 'and';
    private const NODE_OR = 'or';
    private const NODE_REQUEST = 'request';
    private const NODE_MAIL = 'mail';
    private const NODE_RESOLVER = 'resolver';
    private const NODE_END = 'end';

    // キャッシュ設定
    private const CACHE_TTL = 300; // 5分
    private const CACHE_PREFIX = 'approval_flow:';

    public function __construct()
    {
        $this->validateConfiguration();
    }

    /**
     * キャッシュキーを生成する
     */
    private function getCacheKey(string $type, string $identifier): string
    {
        return self::CACHE_PREFIX . $type . ':' . $identifier;
    }

    /**
     * 設定の検証を行う
     */
    private function validateConfiguration(): void
    {
        $requiredConfigs = [
            'roles_model' => config('approval-flow.roles_model'),
            'users_model' => config('approval-flow.users_model'),
            'routes.prefix' => config('approval-flow.routes.prefix'),
        ];

        foreach ($requiredConfigs as $key => $value) {
            if (empty($value)) {
                throw new ConfigurationException("設定項目 '{$key}' が正しく設定されていません");
            }

            if (str_contains($key, '_model') && !class_exists($value)) {
                throw new ConfigurationException("モデルクラス '{$value}' が存在しません");
            }
        }
    }

    /**
     * 承認フローを処理する
     */
    public function processApprovalFlow(array $flow, int $nodeId, ApprovalFlowTask $task, int $applicantId, array $visited = []): void
    {
        if (in_array($nodeId, $visited)) {
            \Log::warning("ループ検出 node_id=$nodeId");
            return;
        }

        $visited[] = $nodeId;
        $node = $this->getNodeData($flow, $nodeId);

        if (!$node) {
            Log::warning("ノードが見つかりません", ['node_id' => $nodeId]);
            return;
        }

        $this->processNode($node, $flow, $nodeId, $task, $applicantId, $visited);

    }

    /**
     * フローからノードデータを取得する
     */
    private function getNodeData(array $flow, int $nodeId): ?array
    {
        return $flow['drawflow']['Home']['data'][$nodeId] ?? null;
    }

    /**
     * ノードタイプに応じて処理を分岐する
     */
    private function processNode(array $node, array $flow, int $nodeId, ApprovalFlowTask $task, int $applicantId, array $visited): void
    {
        match ($node['name']) {
            self::NODE_START => $this->handleStart($flow, $nodeId, $task, $applicantId, $visited),
            self::NODE_AND => $this->handleAnd($flow, $nodeId, $task, $applicantId, $visited),
            self::NODE_OR => $this->handleOr($flow, $nodeId, $task, $applicantId, $visited),
            self::NODE_REQUEST => $this->handleRequest($flow, $nodeId, $task, $applicantId, $visited),
            self::NODE_MAIL => $this->handleMail($flow, $nodeId, $task, $applicantId),
            self::NODE_RESOLVER => $this->handleResolver($flow, $nodeId, $task, $applicantId),
            self::NODE_END => $this->handleEnd($nodeId, $task),
            default => throw new InvalidNodeException("未定義のノードタイプ: {$node['name']}")
        };
    }

    /**
     * 開始ノードの処理
     */
    private function handleStart(array $flow, int $nodeId, ApprovalFlowTask $task, int $applicantId, array $visited): void
    {
        $this->saveHistory($task->id, $nodeId, $applicantId, 'Request', $task->comment);
        $this->updateNode($task, $nodeId);
        $this->processNextNodes($flow, $nodeId, 'output_1', $task, $applicantId, $visited);
    }

    /**
     * ANDゲートの処理（全承認が必要）
     */
    private function handleAnd(array $flow, int $nodeId, ApprovalFlowTask $task, int $applicantId, array $visited): void
    {
        $this->evaluateGate($flow, $nodeId, $task, $applicantId, $visited, true);
    }
    /**
     * ORゲートの処理（一つでも承認があれば通過）
     */
    private function handleOr(array $flow, int $nodeId, ApprovalFlowTask $task, int $applicantId, array $visited): void
    {
        $this->evaluateGate($flow, $nodeId, $task, $applicantId, $visited, false);
    }
    /**
     * ゲート（AND/OR）の評価処理
     */
    private function evaluateGate(array $flow, int $nodeId, ApprovalFlowTask $task, int $applicantId, array $visited, bool $needAllApproval = true): void
    {
        $this->updateNode($task, $nodeId);

        $gateResult = $this->calculateGateResult($flow, $nodeId, $task, $needAllApproval);

        if ($gateResult->isApproved()) {
            $this->processNextNodes($flow, $nodeId, 'output_1', $task, $applicantId, $visited);
        } elseif ($gateResult->isRejected()) {
            $this->rejectTask($task);
            $this->processNextNodes($flow, $nodeId, 'output_2', $task, $applicantId, $visited);
        }
        // まだ完了していない場合は何もしない
    }

    /**
     * ゲートの結果を計算する
     */
    private function calculateGateResult(array $flow, int $nodeId, ApprovalFlowTask $task, bool $needAllApproval): GateResult
    {
        $startHistory = $this->getStartHistory($task);
        $inputNodes = $this->getInputNodes($flow, $nodeId);

        $approvalCount = $this->countApprovals($task, $inputNodes, $startHistory->created_at);
        $hasRejection = $this->hasRejection($task, $inputNodes, $startHistory->created_at);

        return new GateResult(
            approvalCount: $approvalCount,
            requiredApprovals: count($inputNodes),
            hasRejection: $hasRejection,
            needAllApproval: $needAllApproval
        );
    }

    /**
     * 開始履歴を取得する（ハイブリッドキャッシュ対応）
     */
    private function getStartHistory(ApprovalFlowTask $task): ApprovalFlowHistory
    {
        $taskId = $task->id;

        // Level 1: リクエスト内メモリキャッシュ（最速）
        if (isset($this->startHistoryCache[$taskId])) {
            return $this->startHistoryCache[$taskId];
        }

        // Level 2: Laravelキャッシュ（リクエスト間で共有）
        $cacheKey = $this->getCacheKey('start_history', (string)$taskId);

        $result = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($taskId) {
            return ApprovalFlowHistory::where('flow_task_id', $taskId)
                ->where('node_id', self::START_NODE_ID)
                ->latest()
                ->firstOrFail();
        });

        // リクエスト内キャッシュにも保存
        $this->startHistoryCache[$taskId] = $result;

        return $result;
    }

    /**
     * 入力ノードのIDリストを取得する（ハイブリッドキャッシュ対応）
     */
    private function getInputNodes(array $flow, int $nodeId): array
    {
        $cacheKey = $nodeId;

        // Level 1: リクエスト内メモリキャッシュ（最速）
        if (isset($this->inputNodesCache[$cacheKey])) {
            return $this->inputNodesCache[$cacheKey];
        }

        // フロー構造のハッシュを計算（フロー構造が変わった場合のキャッシュ無効化用）
        $flowHash = md5(json_encode($flow['drawflow']['Home']['data'][$nodeId]['inputs'] ?? []));

        // Level 2: Laravelキャッシュ（リクエスト間で共有、長期キャッシュ可能）
        $globalCacheKey = $this->getCacheKey('input_nodes', $nodeId . ':' . $flowHash);

        $result = Cache::remember($globalCacheKey, 3600, function () use ($flow, $nodeId) { // 1時間キャッシュ
            return collect($flow['drawflow']['Home']['data'][$nodeId]['inputs']['input_1']['connections'] ?? [])
                ->pluck('node')
                ->toArray();
        });

        // リクエスト内キャッシュにも保存
        $this->inputNodesCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * 承認数をカウントする（ハイブリッドキャッシュ対応）
     */
    private function countApprovals(ApprovalFlowTask $task, array $inputNodes, $startTime): int
    {
        $taskId = $task->id;
        $nodesKey = implode('-', $inputNodes);
        $cacheKey = $taskId . '_' . $nodesKey;
        $timeKey = md5($startTime);

        // Level 1: リクエスト内メモリキャッシュ（最速）
        if (isset($this->approvalCountCache[$cacheKey])) {
            return $this->approvalCountCache[$cacheKey];
        }

        // Level 2: Laravelキャッシュ（リクエスト間で共有）
        $globalCacheKey = $this->getCacheKey('approvals', "{$taskId}:{$nodesKey}:{$timeKey}");

        $result = Cache::remember($globalCacheKey, self::CACHE_TTL, function () use ($taskId, $inputNodes, $startTime) {
            return ApprovalFlowHistory::where('flow_task_id', $taskId)
                ->whereIn('node_id', $inputNodes)
                ->where('created_at', '>=', $startTime)
                ->where('name', self::APPROVAL_STATUS)
                ->distinct('node_id')
                ->count('node_id');
        });

        // リクエスト内キャッシュにも保存
        $this->approvalCountCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * 却下があるかチェックする（ハイブリッドキャッシュ対応）
     */
    private function hasRejection(ApprovalFlowTask $task, array $inputNodes, $startTime): bool
    {
        $taskId = $task->id;
        $nodesKey = implode('-', $inputNodes);
        $cacheKey = $taskId . '_' . $nodesKey;
        $timeKey = md5($startTime);

        // Level 1: リクエスト内メモリキャッシュ（最速）
        if (isset($this->rejectionCache[$cacheKey])) {
            return $this->rejectionCache[$cacheKey];
        }

        // Level 2: Laravelキャッシュ（リクエスト間で共有）
        $globalCacheKey = $this->getCacheKey('rejections', "{$taskId}:{$nodesKey}:{$timeKey}");

        $result = Cache::remember($globalCacheKey, self::CACHE_TTL, function () use ($taskId, $inputNodes, $startTime) {
            return ApprovalFlowHistory::where('flow_task_id', $taskId)
                ->whereIn('node_id', $inputNodes)
                ->where('created_at', '>=', $startTime)
                ->where('name', self::REJECTION_STATUS)
                ->exists();
        });

        // リクエスト内キャッシュにも保存
        $this->rejectionCache[$cacheKey] = $result;

        return $result;
    }



    // evaluateGate2メソッドは削除（リファクタリング済みの新しい実装に統合）

    /**
     * 承認要求ノードの処理
     */
    private function handleRequest(array $flow, int $nodeId, ApprovalFlowTask $task, int $applicantId, array $visited    ): void
    {
        $nodeData = $this->getNodeData($flow, $nodeId);
        $users = $this->resolveUsersForNode($nodeData, $task, $applicantId);

        $post = $this->getPostFromNodeData($nodeData, $task);
        $task->link = $this->generateApprovalLink($task, $nodeId, $post);

        $this->notifyUsers($users, $task, config('approval-flow.notification_titles.approval_request', '承認申請'));
    }

    /**
     * メール通知ノードの処理
     */
    private function handleMail(array $flow, int $nodeId, ApprovalFlowTask $task, int $applicantId): void
    {

        $nodeData = $this->getNodeData($flow, $nodeId);

        $users = $this->resolveUsersForNode($nodeData, $task, $applicantId);

        $task->msg = $nodeData['data']['contents'] ?? '';
        $task->link = $this->generateDetailLink($task);

        $this->notifyUsers($users, $task, config('approval-flow.notification_titles.workflow_notification', 'ワークフロー通知')
        );
    }

    /**
     * Resolver ノードの処理（動的に役職IDを解決して承認依頼を送る）
     */
    private function handleResolver(array $flow, int $nodeId, ApprovalFlowTask $task, int $applicantId): void
    {
        $nodeData = $this->getNodeData($flow, $nodeId);
        $resolverClass = $nodeData['data']['resolver_class'] ?? null;
        $params = $nodeData['data']['params'] ?? [];

        // params が文字列(JSON)なら配列に
        if (is_string($params)) {
            try {
                $decoded = json_decode($params, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $params = $decoded;
                }
            } catch (\Throwable $e) {
                // パラメータがJSONでない場合は空配列として扱う
                $params = [];
            }
        }

        // ホワイトリストチェック
        $allowed = array_keys((array) config('approval-flow.resolvers', []));
        if (!$resolverClass || !in_array($resolverClass, $allowed, true)) {
            Log::warning('未許可のResolverクラス、または未指定です', [
                'node_id' => $nodeId,
                'resolver_class' => $resolverClass,
            ]);
            return;
        }

        try {
            $resolver = app($resolverClass);
        } catch (\Throwable $e) {
            Log::error('Resolverのインスタンス化に失敗しました', [
                'class' => $resolverClass,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        // 想定インターフェース: resolveRoleId(User $applicant, ApprovalFlowTask $task, array $params): ?int
        $applicant = $task->user;
        $roleId = null;
        try {
            if (method_exists($resolver, 'resolveRoleId')) {
                $roleId = (int) ($resolver->resolveRoleId($applicant, $task, (array) $params) ?? 0);
            }
        } catch (\Throwable $e) {
            Log::error('Resolverの実行に失敗しました', [
                'class' => $resolverClass,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        if (!$roleId) {
            Log::warning('Resolverが有効な役職IDを返しませんでした', [
                'class' => $resolverClass,
                'node_id' => $nodeId,
            ]);
            return;
        }

        // 役職のユーザーを取得して承認依頼を通知
        $rolesModel = config('approval-flow.roles_model');
        $role = class_exists($rolesModel) ? $rolesModel::find($roleId) : null;
        $users = $role?->users ?? collect();

        // リンクは request と同様、post に役職IDを持たせる
        $task->link = $this->generateApprovalLink($task, $nodeId, $roleId);

        $this->notifyUsers($users, $task, config('approval-flow.notification_titles.approval_request', '承認申請'));
    }


    // handleMail2メソッドは削除（リファクタリング済みの新しい実装に統合）

    /**
     * 終了ノードの処理
     */
    private function handleEnd(int $nodeId, ApprovalFlowTask $task): void
    {
        $task->update([
            'node_id' => $nodeId,
            'status' => 'approved',
            'is_complete' => true,
        ]);

        $task->target?->onApproved();

        $task->link = $this->generateDetailLink($task);
        $this->notifyUsers($task->user, $task, config('approval-flow.notification_titles.approval_completed', '承認完了'));
    }

    /**
     * ノードデータからユーザーを解決する
     */
    private function resolveUsersForNode(array $nodeData, ApprovalFlowTask $task, int $applicantId): Collection|SupportCollection
    {
        $rolesModel = config('approval-flow.roles_model');
        $userModel = config('approval-flow.users_model');

        // システムロールの処理
        if ($this->hasSystemRole($nodeData)) {
            $roleId = $task->system_roles[$nodeData['data']['system']];
            return $rolesModel::find($roleId)?->users ?? collect();
        }

        $post = $nodeData['data']['post'] ?? null;

        // 申請者への通知（post = 0）
        if ($post === '0') {
            $user = $userModel::find($applicantId);
            return $user ? collect([$user]) : collect();
        }

        // 役職への通知
        if ($post) {
            return $rolesModel::find($post)?->users ?? collect();
        }

        return collect();
    }

    /**
     * システムロールが設定されているかチェック
     */
    private function hasSystemRole(array $nodeData): bool
    {
        return isset($nodeData['data']['system']) &&
           ($nodeData['data']['system'] !== '' && $nodeData['data']['system'] !== null);
    }

    /**
     * ノードデータから役職IDを取得
     */
    private function getPostFromNodeData(array $nodeData, ApprovalFlowTask $task): int
    {
        if ($this->hasSystemRole($nodeData)) {
            return (int) $task->system_roles[$nodeData['data']['system']];
        }

        return (int) $nodeData['data']['post'] ?? 0;
    }

    /**
     * 承認用リンクを生成
     */
    private function generateApprovalLink(ApprovalFlowTask $task, int $nodeId, int $post): string
    {
        $prefix = config('approval-flow.routes.prefix');
        return route("{$prefix}.detail", $task->id) . "?node={$nodeId}&post={$post}";
    }

    /**
     * 詳細表示用リンクを生成
     */
    private function generateDetailLink(ApprovalFlowTask $task): string
    {
        $prefix = config('approval-flow.routes.prefix');
        return route("{$prefix}.detail", $task->id);
    }

    /**
     * 次のノードを処理する
     */
    public function processNextNodes(array $flow, int $nodeId, string $outputKey, ApprovalFlowTask $task, int $applicantId, array $visited): void
    {
        $connections = $flow['drawflow']['Home']['data'][$nodeId]['outputs'][$outputKey]['connections'] ?? [];

        foreach ($connections as $connection) {
            $this->processApprovalFlow($flow, $connection['node'], $task, $applicantId, $visited);
        }
    }

    /**
     * タスクを却下状態にする
     */
    public function rejectTask(ApprovalFlowTask $task): void
    {
        $task->update(['status' => 'rejected', 'is_complete' => true]);

        // キャッシュをクリア
        $this->clearCache($task->id);

        $task->link = $this->generateDetailLink($task);
        $this->notifyUsers($task->user, $task, config('approval-flow.notification_titles.request_rejected', '申請却下'));
        $task->target?->onRejected();
    }

    /**
     * 履歴を保存する
     */
    public function saveHistory(int $taskId, int $nodeId, int $userId, string $action, ?string $comment): void
    {
        ApprovalFlowHistory::create(['flow_task_id' => $taskId, 'node_id' => $nodeId, 'user_id' => $userId, 'name' => $action, 'comment' => $comment,]);

        // 履歴が変更されたらキャッシュをクリア
        $this->clearCache($taskId);
    }

    /**
     * ユーザーに通知を送信する
     */
    public function notifyUsers($users, ApprovalFlowTask $task, string $title): void
    {
        // コレクションでなければコレクションに変換
        if (!is_null($users) && !is_object($users)) {
            $users = collect([$users]);
        }

        if (empty($users) || (is_object($users) && method_exists($users, 'isEmpty') && $users->isEmpty())) {
            Log::warning('通知対象ユーザーが存在しません', ['task_id' => $task->id]);
            return;
        }

        try {
            // 通知オブジェクトを一度だけ作成
            $notification = new ApprovalFlowNotification($task, $title);
            Notification::send($users, $notification);
        } catch (\Exception $e) {
            Log::error('通知送信に失敗しました', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * タスクのノードIDを更新する
     */
    private function updateNode(ApprovalFlowTask $task, int $nodeId): void
    {
        $task->update(['node_id' => $nodeId]);
    }

    /**
     * ハイブリッドキャッシュをクリアする
     */
    private function clearCache(int $taskId = null): void
    {
        if ($taskId) {
            // 特定のタスクに関連するキャッシュのみクリア

            // Level 1: メモリキャッシュクリア
            unset($this->startHistoryCache[$taskId]);

            foreach ($this->approvalCountCache as $key => $value) {
                if (strpos($key, $taskId . '_') === 0) {
                    unset($this->approvalCountCache[$key]);
                }
            }

            foreach ($this->rejectionCache as $key => $value) {
                if (strpos($key, $taskId . '_') === 0) {
                    unset($this->rejectionCache[$key]);
                }
            }

            // Level 2: Laravelキャッシュクリア
            $pattern = self::CACHE_PREFIX . '*' . $taskId . '*';
            $this->clearCacheByPattern($pattern);

            // 特定のキーも明示的にクリア
            Cache::forget($this->getCacheKey('start_history', (string)$taskId));
        } else {
            // 全キャッシュクリア

            // Level 1: メモリキャッシュクリア
            $this->startHistoryCache = [];
            $this->inputNodesCache = [];
            $this->approvalCountCache = [];
            $this->rejectionCache = [];

            // Level 2: Laravelキャッシュクリア
            $pattern = self::CACHE_PREFIX . '*';
            $this->clearCacheByPattern($pattern);
        }
    }

    /**
     * パターンに一致するキャッシュをクリアする
     * (注: これはドライバーによって異なる実装が必要な場合があります)
     */
    private function clearCacheByPattern(string $pattern): void
    {
        // Redis用の実装例
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);

            if (!empty($keys)) {
                $redis->del($keys);
            }
        } else {
            // それ以外のキャッシュドライバの場合は
            // すべてのキャッシュをクリアするなどの代替手段を検討
            Cache::flush(); // 全キャッシュクリア（注意: 運用環境では注意が必要）
        }
    }



}
