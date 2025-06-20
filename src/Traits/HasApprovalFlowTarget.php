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
     */
    public function registerWorkflowTask(int $workflowId, int $authorId, ?string $comment = null): ApprovalFlowTask
    {
        $task = ApprovalFlowTask::create([
            'flow_id'   => $workflowId,
            'user_id'       => $authorId,
            'ref_id'        => $this->getKey(),
            'system_type'   => static::class,
            'status'        => '未承認',
            'is_complete'   => false,
        ]);

        if ($comment !== null) {
            $task->comment = $comment;
        }

        $task->save();

        $flowData = ApprovalFlow::findOrFail($workflowId)->flow['drawflow']['Home']['data'] ?? [];

        app(ApprovalFlowService::class)
            ->processApprovalFlow($flowData, 1, $task, $authorId);

        return $task;
    }

    /**
     * 承認時の振る舞い（必要に応じてモデル側でオーバーライド）
     */
    public function onApproved(): void
    {
        $this->update(['status' => '承認済み']);
    }

    /**
     * 却下時の振る舞い（必要に応じてモデル側でオーバーライド）
     */
    public function onRejected(): void
    {
        $this->update(['status' => '却下']);
    }
}
