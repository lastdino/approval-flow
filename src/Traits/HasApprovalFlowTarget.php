<?php

namespace Lastdino\ApprovalFlow\Traits;

use Lastdino\ApprovalFlow\Models\ApprovalFlow;
use Lastdino\ApprovalFlow\Models\ApprovalFlowTask;
use Lastdino\ApprovalFlow\Services\ApprovalFlowService;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\App;

trait HasApprovalFlowTarget
{
    /**
     * モデルに紐づく承認タスク（1件）
     */
    public function ApprovalFlowTask(): MorphOne
    {
        return $this->morphOne(ApprovalFlowTask::class, 'target', 'system_type', 'ref_id');
    }

    /**
     * ワークフロー申請を登録
     * @param int $flowId フローID
     * @param int $authorId 申請者ID
     * @param string|null $comment コメント
     * @param array|null $systemRoles システムロール
     * @return ApprovalFlowTask
     */
    public function registerApprovalFlowTask(int $flowId, int $authorId, ?string $comment = null, ?array $systemRoles = null): ApprovalFlowTask
    {
        $task = ApprovalFlowTask::create([
            'flow_id'       => $flowId,
            'user_id'       => $authorId,
            'ref_id'        => $this->getKey(),
            'system_type'   => static::class,
            'status'        => 'pending',
            'is_complete'   => false,
            'system_roles'  => $systemRoles,
        ]);

        if ($comment !== null) {
            $task->comment = $comment;
        }

        $task->save();

        $flowData = ApprovalFlow::findOrFail($flowId)->flow ?? [];

        app(ApprovalFlowService::class)
            ->processApprovalFlow($flowData, 1, $task, $authorId);

        return $task;
    }

    /**
     * 承認時の振る舞い（必要に応じてモデル側でオーバーライド）
     */
    public function onApproved(): void
    {
        $this->update(['status' => 'approved']);
    }

    /**
     * 却下時の振る舞い（必要に応じてモデル側でオーバーライド）
     */
    public function onRejected(): void
    {
        $this->update(['status' => 'rejected']);
    }

    /**
     * キャンセル時の振る舞い（必要に応じてモデル側でオーバーライド）
     */
    public function onCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * ワークフロー申請をキャンセル
     * @param int $userId キャンセルを実行するユーザーID
     * @param string|null $comment キャンセル理由
     * @return bool
     */
    public function cancelApprovalFlowTask(int $userId, ?string $comment = null): bool
    {
        $task = $this->ApprovalFlowTask;

        if (!$task || $task->is_complete) {
            return false;
        }

        $task->update([
            'status' => 'cancelled',
            'is_complete' => true
        ]);

        app(ApprovalFlowService::class)->saveHistory(
            $task->id,
            $task->node_id,
            $userId,
            'Cancelled',
            $comment
        );

        $task->link = route(config('approval-flow.routes.prefix').'.detail', $task->id);
        app(ApprovalFlowService::class)->notifyUsers($task->user, $task, config('approval-flow.notification_titles.request_cancelled', '申請キャンセル'));

        $this->onCancelled();

        return true;
    }
}
