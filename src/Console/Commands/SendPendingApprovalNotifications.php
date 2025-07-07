<?php

namespace Lastdino\ApprovalFlow\Console\Commands;

use Illuminate\Console\Command;
use Lastdino\ApprovalFlow\Models\ApprovalFlowTask;
use Lastdino\ApprovalFlow\Services\ApprovalFlowService;


class SendPendingApprovalNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approval-flow:notify-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '未承認タスクに対して通知を送信';

    /**
     * Execute the console command.
     */
    public function handle(ApprovalFlowService $service)
    {
        $pendingTasks = ApprovalFlowTask::where('status', 'pending')
            ->where('is_complete', false)
            ->get();

        $this->info("未承認タスク {$pendingTasks->count()} 件を処理中...");

        foreach ($pendingTasks as $task) {
            $this->sendPendingNotification($task, $service);
        }
        $this->info('通知送信完了');
    }

    private function sendPendingNotification(ApprovalFlowTask $task, ApprovalFlowService $service)
    {
        // 現在のノードの承認者を取得
        $flow = $task->flow->flow_data;
        $currentNodeId = $task->node_id;

        $nodeData = $flow['drawflow']['Home']['data'][$currentNodeId] ?? null;

        if (!$nodeData || $nodeData['name'] !== 'request') {
            return;
        }

        // 承認者を取得
        $users = $this->resolveApprovers($nodeData, $task);

        if ($users->isEmpty()) {
            $this->warn("タスクID {$task->id} の承認者が見つかりません");
            return;
        }

        // 催促通知を送信
        $task->link = $this->generateApprovalLink($task, $currentNodeId);
        $task->msg = "承認申請から " . $task->created_at->diffForHumans() . " が経過しています。";

        $service->notifyUsers(
            $users,
            $task,
            '【催促】承認申請の確認をお願いします'
        );

        $this->info("タスクID {$task->id} に催促通知を送信しました");
    }
    private function resolveApprovers($nodeData, $task)
    {
        $rolesModel = config('approval-flow.roles_model');

        if (isset($nodeData['data']['system']) && $nodeData['data']['system'] !== '') {
            $roleId = $task->system_roles[$nodeData['data']['system']];
            return $rolesModel::find($roleId)?->users ?? collect();
        }

        $post = $nodeData['data']['post'] ?? null;
        if ($post && $post !== '0') {
            return $rolesModel::find($post)?->users ?? collect();
        }

        return collect();
    }

    private function generateApprovalLink($task, $nodeId)
    {
        $prefix = config('approval-flow.routes.prefix');
        return route("{$prefix}.detail", $task->id) . "?node={$nodeId}";
    }



}
