<?php

namespace Lastdino\ApprovalFlow\Livewire\ApprovalFlow;

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use Lastdino\ApprovalFlow\Models\ApprovalFlowTask;
use Lastdino\ApprovalFlow\Services\ApprovalFlowService;

use Flux\Flux;

class Detail extends Component
{
    public ApprovalFlowTask $task;
    public $Detail = [];
    public $file = [];
    public $admin = false;
    #[Url]
    public $node;
    #[Url]
    public $post;
    public $comment = '';

    protected ApprovalFlowService $approvalFlowService;

    public function boot(ApprovalFlowService $approvalFlowService)
    {
        $this->approvalFlowService = $approvalFlowService;
    }


    /**
     * タスク情報を初期化する
     *
     * @param ApprovalFlowTask $task 承認フロータスクのインスタンス
     */
    public function mount(ApprovalFlowTask $task)
    {
        $this->task = $task->load('user', 'histories.user');
        $this->node = request('node');
        $this->post = request('post');

        // 添付ファイル（仮の例：Spatie MediaLibraryなど）
        //$this->file = $task->target->getAttachedFiles() ?? [];


        //承認権限の判定
        $roleId = (int)$this->post;
        $this->admin = auth()->user()?->roles->contains('id', $roleId);

        // 既に承認または拒否されている場合は管理者判定をfalseにする
        $nodeHistories = $this->task->histories->where('node_id', $this->node);
        if ($nodeHistories->contains('name', '承認') || $nodeHistories->contains('name', '却下')) {
            $this->admin = false;
        }
    }

    /**
     * 承認する
     */
    public function approve()
    {
        // 権限チェック
        if (!$this->admin) {
            Flux::toast(variant: 'danger', text: '権限がありません');
            return;
        }

        // 承認履歴を追加
        $this->approvalFlowService->saveHistory(
            $this->task->id,
            $this->node,
            auth()->id(),
            'Approved',
            $this->comment
        );

        // フローを進める
        $flow = $this->task->flow->flow;
        $this->approvalFlowService->next(
            $flow,
            $flow['drawflow']['Home']['data'][$this->node]['outputs']['output_1']['connections'],
            $this->task,
            $this->task->user_id,
            [$this->node]
        );

        // コメントをリセット
        $this->comment = '';

        // 成功メッセージを表示
        Flux::toast(variant: 'success', text: '承認しました');
        $this->admin = false;

        // 最新のタスク情報をリロード
        $this->task = $this->task->fresh()->load('user', 'histories.user', 'flow');
    }

    /**
     * 却下する
     */
    public function reject()
    {
        // 権限チェック
        if (!$this->admin) {
            Flux::toast(variant: 'danger', text: '権限がありません');
            return;
        }

        // 却下履歴を追加
        $this->approvalFlowService->saveHistory(
            $this->task->id,
            $this->node,
            auth()->id(),
            'Rejected',
            $this->comment
        );

        // フローを進める（却下ルート）
        $flow = $this->task->flow->flow;
        $this->approvalFlowService->next(
            $flow,
            $flow['drawflow']['Home']['data'][$this->node]['outputs']['output_1']['connections'],
            $this->task,
            $this->task->user_id,
            [$this->node]
        );

        // コメントをリセット
        $this->comment = '';

        // 成功メッセージを表示
        Flux::toast(variant: 'success', text: '却下しました');
        $this->admin = false;

        // 最新のタスク情報をリロード
        $this->task = $this->task->fresh()->load('user', 'histories.user', 'flow');
    }



    public function render()
    {
        return view('approval-flow::livewire.approval-flow.detail');
    }
}
