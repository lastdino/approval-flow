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
        $flow = $task->flow->flow;
        $currentNodeId = $task->node_id;

        $service->processApprovalFlow($flow,$currentNodeId,$task,$task->user_id);

    }
}
