<?php

namespace Lastdino\ApprovalFlow\Services;

use App\Notifications\InformationNotification;
use Illuminate\Support\Facades\Notification;
use Lastdino\ApprovalFlow\Models\ApprovalFlowHistory;
use Lastdino\ApprovalFlow\Models\ApprovalFlowTask;

class ApprovalFlowService
{
    public function processApprovalFlow($flow, $nodeId, ApprovalFlowTask $task, $applicantId, $admins = [], $visited = [])
    {
        if (in_array($nodeId, $visited)) {
            \Log::warning("ループ検出 node_id=$nodeId");
            return;
        }

        $visited[] = $nodeId;
        $node = $flow[$nodeId] ?? null;

        if (!$node) return;

        match ($node['name']) {
            'start' => $this->handleStart($flow, $nodeId, $task, $applicantId, $admins, $visited),
            'and' => $this->handleAnd($flow, $nodeId, $task, $applicantId, $admins, $visited),
            'or' => $this->handleOr($flow, $nodeId, $task, $applicantId, $admins, $visited),
            'request' => $this->handleRequest($flow, $nodeId, $task, $applicantId, $admins, $visited),
            'mail' => $this->handleMail($flow, $nodeId, $task, $applicantId),
            'end' => $this->handleEnd($nodeId, $task),
            default => \Log::warning("未定義ノード: " . $node['name']),
        };
    }

    private function handleStart($flow, $nodeId, ApprovalFlowTask $task, $applicantId, $admins, $visited)
    {
        $this->saveHistory($task->id, $nodeId, $applicantId, '申請', $task->comment);
        $this->updateNode($task, $nodeId);
        $this->next($flow, $flow[$nodeId]['outputs']['output_1']['connections'], $task, $applicantId, $admins, $visited);
    }

    private function evaluateGate($flow, $nodeId, ApprovalFlowTask $task, $applicantId, $admins, $visited, $needAllApproval = true)
    {
        $this->updateNode($task, $nodeId);
        $start = ApprovalFlowHistory::where('flow_task_id', $task->id)->where('node_id', 1)->latest()->first();
        $inputs = collect($flow[$nodeId]['inputs']['input_1']['connections'])->pluck('node')->toArray();

        $approved = ApprovalFlowHistory::where('flow_task_id', $task->id)
            ->whereIn('node_id', $inputs)
            ->where('created_at', '>=', $start->created_at)
            ->where('name', '承認');

        if ($needAllApproval) {
            $approved = $approved->distinct('node_id')->count('node_id');
        } else {
            $approved = $approved->exists();
        }

        $rejected = ApprovalFlowHistory::where('flow_task_id', $task->id)
            ->whereIn('node_id', $inputs)
            ->where('created_at', '>=', $start->created_at)
            ->where('name', '却下')->exists();

        if (($needAllApproval && $approved === count($inputs)) || (!$needAllApproval && $approved)) {
            $this->next($flow, $flow[$nodeId]['outputs']['output_1']['connections'], $task, $applicantId, $admins, $visited);
        } elseif ($rejected) {
            $this->rejectTask($task);
            $this->next($flow, $flow[$nodeId]['outputs']['output_2']['connections'], $task, $applicantId, $admins, $visited);
        }
    }

    private function handleAnd($flow, $nodeId, ApprovalFlowTask $task, $applicantId, $admins, $visited)
    {
        $this->evaluateGate($flow, $nodeId, $task, $applicantId, $admins, $visited, true);
    }

    private function handleOr($flow, $nodeId, ApprovalFlowTask $task, $applicantId, $admins, $visited)
    {
        $this->evaluateGate($flow, $nodeId, $task, $applicantId, $admins, $visited, false);
    }

    private function handleRequest($flow, $nodeId, ApprovalFlowTask $task, $applicantId, $admins, $visited)
    {
        $rolesModel=config('approval-flow.roles_model');
        if(!class_exists($rolesModel)){
            \App\Services\alert("`roles_model` not configured");
        }

        $post = $flow[$nodeId]['data']['post'];
        $users = ($rolesModel)::query()->find($post)?->user ?? collect();
        $this->notifyUsers($users, $task, '承認申請', $nodeId, $post);
    }

    private function handleMail($flow, $nodeId, ApprovalFlowTask $task, $applicantId)
    {
        $userModel=config('approval-flow.users_model');
        if(!class_exists($userModel)){
            \App\Services\alert("`user_model` not configured");
        }

        $post = $flow[$nodeId]['data']['post'];
        $users = $post == 0 ? ($userModel)::query()->find($applicantId) : ($userModel)::query()->find($post)?->user ?? collect();
        $task->msg = $flow[$nodeId]['data']['contents'] ?? '';
        $task->link = route(config('approval-flow.routes.prefix').'.detail', $task->id);
        Notification::send($users, new InformationNotification($task, 'ワークフロー通知'));
    }

    private function handleEnd($nodeId, ApprovalFlowTask $task)
    {
        $task->update([
            'node_id' => $nodeId,
            'status' => '承認済み',
            'is_complete' => true,
        ]);

        $task->target?->onApproved();
        Notification::send($task->user, new InformationNotification($task, '承認完了'));
    }

    public function rejectTask(ApprovalFlowTask $task)
    {
        $task->update(['status' => '却下', 'is_complete' => true]);
        $task->target?->onRejected();
    }

    private function saveHistory($taskId, $nodeId, $userId, $action, $comment)
    {
        ApprovalFlowHistory::create([
            'flow_task_id' => $taskId,
            'node_id' => $nodeId,
            'user_id' => $userId,
            'name' => $action,
            'comment' => $comment,
        ]);
    }

    private function notifyUsers($users, $task, $title, $nodeId = null, $post = null)
    {
        $task->link = route(config('approval-flow.routes.prefix').'.detail', $task->id) . "?node=$nodeId&post=$post";
        Notification::send($users, new InformationNotification($task, $title));
    }

    private function updateNode(ApprovalFlowTask $task, int $nodeId): void
    {
        $task->update(['node_id' => $nodeId]);
    }

    private function next($flow, $connections, $task, $applicantId, $admins, $visited)
    {
        foreach ($connections as $conn) {
            $this->processApprovalFlow($flow, $conn['node'], $task, $applicantId, $admins, $visited);
        }
    }
}
