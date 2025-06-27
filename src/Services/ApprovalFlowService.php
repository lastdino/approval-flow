<?php

namespace Lastdino\ApprovalFlow\Services;

use Lastdino\ApprovalFlow\Notifications\ApprovalFlowNotification;
use Illuminate\Support\Facades\Notification;
use Lastdino\ApprovalFlow\Models\ApprovalFlowHistory;
use Lastdino\ApprovalFlow\Models\ApprovalFlowTask;

class ApprovalFlowService
{
    public function processApprovalFlow($flow, $nodeId, ApprovalFlowTask $task, $applicantId, $visited = [])
    {
        if (in_array($nodeId, $visited)) {
            \Log::warning("ループ検出 node_id=$nodeId");
            return;
        }

        $visited[] = $nodeId;
        $node = $flow['drawflow']['Home']['data'][$nodeId] ?? null;

        if (!$node) return;
        match ($node['name']) {
            'start' => $this->handleStart($flow, $nodeId, $task, $applicantId, $visited),
            'and' => $this->handleAnd($flow, $nodeId, $task, $applicantId, $visited),
            'or' => $this->handleOr($flow, $nodeId, $task, $applicantId, $visited),
            'request' => $this->handleRequest($flow, $nodeId, $task, $applicantId, $visited),
            'mail' => $this->handleMail($flow, $nodeId, $task, $applicantId),
            'end' => $this->handleEnd($nodeId, $task),
            default => \Log::warning("未定義ノード: " . $node['name']),
        };
    }

    private function handleStart($flow, $nodeId, ApprovalFlowTask $task, $applicantId, $visited)
    {
        $this->saveHistory($task->id, $nodeId, $applicantId, 'Request', $task->comment);
        $this->updateNode($task, $nodeId);
        $this->next($flow, $flow['drawflow']['Home']['data'][$nodeId]['outputs']['output_1']['connections'], $task, $applicantId, $visited);
    }

    private function evaluateGate($flow, $nodeId, ApprovalFlowTask $task, $applicantId, $visited, $needAllApproval = true)
    {
        $this->updateNode($task, $nodeId);
        $start = ApprovalFlowHistory::where('flow_task_id', $task->id)->where('node_id', 1)->latest()->first();
        $inputs = collect($flow['drawflow']['Home']['data'][$nodeId]['inputs']['input_1']['connections'])->pluck('node')->toArray();

        $approved = ApprovalFlowHistory::where('flow_task_id', $task->id)
            ->whereIn('node_id', $inputs)
            ->where('created_at', '>=', $start->created_at)
            ->where('name', 'Approved');

        if ($needAllApproval) {
            $approved = $approved->distinct('node_id')->count('node_id');
        } else {
            $approved = $approved->exists();
        }

        $rejected = ApprovalFlowHistory::where('flow_task_id', $task->id)
            ->whereIn('node_id', $inputs)
            ->where('created_at', '>=', $start->created_at)
            ->where('name', 'Rejected')->exists();

        if (($needAllApproval && $approved === count($inputs)) || (!$needAllApproval && $approved)) {
            $this->next($flow, $flow['drawflow']['Home']['data'][$nodeId]['outputs']['output_1']['connections'], $task, $applicantId, $visited);
        } elseif ($rejected) {
            $this->rejectTask($task);
            $this->next($flow, $flow['drawflow']['Home']['data'][$nodeId]['outputs']['output_2']['connections'], $task, $applicantId, $visited);
        }
    }

    private function handleAnd($flow, $nodeId, ApprovalFlowTask $task, $applicantId, $visited)
    {
        $this->evaluateGate($flow, $nodeId, $task, $applicantId,  $visited, true);
    }

    private function handleOr($flow, $nodeId, ApprovalFlowTask $task, $applicantId, $visited)
    {
        $this->evaluateGate($flow, $nodeId, $task, $applicantId,  $visited, false);
    }

    private function handleRequest($flow, $nodeId, ApprovalFlowTask $task, $applicantId, $visited)
    {
        $rolesModel = config('approval-flow.roles_model');
        if(!class_exists($rolesModel)){
            \Log::warning("roles_model` not configured");
        }

        // systemパラメータがある場合はtaskのsystem_rolesを使用
        if (!empty($flow['drawflow']['Home']['data'][$nodeId]['data']['system'])) {
            $users = ($rolesModel)::query()->find($task->system_roles[$flow['drawflow']['Home']['data'][$nodeId]['data']['system']])?->users ?? collect();
            $post = $task->system_roles[$flow['drawflow']['Home']['data'][$nodeId]['data']['system']];
        } else {
            $post = $flow['drawflow']['Home']['data'][$nodeId]['data']['post'];
            $users = ($rolesModel)::query()->find($post)?->users ?? collect();
        }
        $task->link = route(config('approval-flow.routes.prefix').'.detail', $task->id) . "?node=$nodeId&post=$post";
        $this->notifyUsers($users, $task, config('approval-flow.notification_titles.approval_request', '承認申請'));
    }

    private function handleMail($flow, $nodeId, ApprovalFlowTask $task, $applicantId)
    {
        $rolesModel = config('approval-flow.roles_model');
        $userModel=config('approval-flow.users_model');
        if(!class_exists($rolesModel)){
            \Log::warning("roles_model` not configured");
        }
        if(!class_exists($userModel)){
            \Log::warning("user_model` not configured");
        }

        $post = $flow['drawflow']['Home']['data'][$nodeId]['data']['post'];

        if($post == 0){
            $users = ($userModel)::query()->find($applicantId);
        }elseif (isset($flow['drawflow']['Home']['data'][$nodeId]['data']['system'])){
            // systemパラメータがある場合はtaskのsystem_rolesを使用
            $users = ($rolesModel)::query()->find($task->system_roles[$flow['drawflow']['Home']['data'][$nodeId]['data']['system']])?->users ?? collect();
        }else{
            $users = ($rolesModel)::query()->find($post)?->users ?? collect();
        }

        $task->msg = $flow['drawflow']['Home']['data'][$nodeId]['data']['contents'] ?? '';
        $task->link = route(config('approval-flow.routes.prefix').'.detail', $task->id);

        $this->notifyUsers($users, $task,config('approval-flow.notification_titles.workflow_notification', 'ワークフロー通知'));
    }

    private function handleEnd($nodeId, ApprovalFlowTask $task)
    {
        $task->update([
            'node_id' => $nodeId,
            'status' => 'approved',
            'is_complete' => true,
        ]);

        $task->target?->onApproved();

        $task->link = route(config('approval-flow.routes.prefix').'.detail', $task->id);
        $this->notifyUsers($task->user, $task, config('approval-flow.notification_titles.approval_completed', '承認完了'));
    }

    public function rejectTask(ApprovalFlowTask $task)
    {
        $task->update(['status' => 'rejected', 'is_complete' => true]);

        $task->link = route(config('approval-flow.routes.prefix').'.detail', $task->id);
        $this->notifyUsers($task->user, $task, config('approval-flow.notification_titles.request_rejected', '申請却下'));
        $task->target?->onRejected();
    }

    public function saveHistory($taskId, $nodeId, $userId, $action, $comment)
    {
        ApprovalFlowHistory::create([
            'flow_task_id' => $taskId,
            'node_id' => $nodeId,
            'user_id' => $userId,
            'name' => $action,
            'comment' => $comment,
        ]);
    }

    public function notifyUsers($users, $task, $title)
    {
        Notification::send($users, new ApprovalFlowNotification($task, $title));
    }

    private function updateNode(ApprovalFlowTask $task, int $nodeId): void
    {
        $task->update(['node_id' => $nodeId]);
    }

    public function next($flow, $connections, $task, $applicantId, $visited)
    {
        foreach ($connections as $conn) {
            $this->processApprovalFlow($flow, $conn['node'], $task, $applicantId,  $visited);
        }
    }
}
