<?php
namespace App\Traits;

use App\Models\Workflow;
use App\Models\WorkflowTask;

trait HasWorkflowTarget
{
    /**
     * モデルに紐づく承認タスク
     */
    public function workflowTask()
    {
        return $this->morphOne(WorkflowTask::class, 'target', 'system_type', 'ref_id');
    }

    /**
     * ワークフロー申請を登録
     */
    public function registerWorkflowTask(int $workflowId, int $authorId, ?string $comment = null): WorkflowTask
    {
        $task = WorkflowTask::create([
            'workflow_id'    => $workflowId,
            'user_id'        => $authorId,
            'ref_id'         => $this->getKey(),
            'system_type'    => static::class,
            'status'         => '未承認',
            'is_complete'    => false,
        ]);

        $task->setAttribute('comment', $comment);
        $task->save();

        $flow = Workflow::findOrFail($workflowId)->flow['drawflow']['Home']['data'];
        app(\App\Services\WorkflowService::class)->processWorkflow($flow, 1, $task, $authorId);

        return $task;
    }

    /**
     * 承認時の振る舞い（オーバーライド可）
     */
    public function onApproved(): void
    {
        $this->update(['status' => '承認済み']);
    }

    /**
     * 却下時の振る舞い（オーバーライド可）
     */
    public function onRejected(): void
    {
        $this->update(['status' => '却下']);
    }
}
