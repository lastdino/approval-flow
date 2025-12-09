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
        if ($nodeHistories->contains('name', 'Approved') || $nodeHistories->contains('name', 'Rejected') || $this->task->is_complete) {
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
            Flux::toast(variant: 'danger', text: __('approval-flow::detail.messages.permission_denied'));
            return;
        }
        Flux::modal('approve-modal')->close();

        // 承認履歴を追加
        $this->approvalFlowService->saveHistory(
            $this->task->id,
            $this->node,
            auth()->id(),
            'Approved',
            $this->comment
        );

        // フローを進める（B案: Resolverチェーン対応）
        $flow = $this->task->flow->flow;

        // 現在ノードが Resolver で、チェーンの残りがある場合は次の承認者へ通知して同ノードで継続
        if ($this->approvalFlowService->continueResolverChainIfAny($flow, (int) $this->node, $this->task)) {
            // コメントをリセット
            $this->comment = '';

            // 同一ノード内で次の承認者待ちに切り替え
            Flux::toast(variant: 'success', text: __('approval-flow::detail.messages.approved'));
            $this->admin = false;

            // 最新のタスク情報をリロード
            $this->task = $this->task->fresh()->load('user', 'histories.user', 'flow');

            return; // 下流ノードへは進まない
        }

        // チェーンが無い or チェーン完了 → 次のノードへ
        $this->approvalFlowService->processNextNodes($flow, (int) $this->node, 'output_1', $this->task, $this->task->user_id, [$this->node]);


        // コメントをリセット
        $this->comment = '';

        // 成功メッセージを表示
        Flux::toast(variant: 'success', text: __('approval-flow::detail.messages.approved'));
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
            Flux::toast(variant: 'danger', text: __('approval-flow::detail.messages.permission_denied'));
            return;
        }
        Flux::modal('reject-modal')->close();

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

        $this->approvalFlowService->processNextNodes($flow, $this->node, 'output_1', $this->task, $this->task->user_id, [$this->node]);

        // コメントをリセット
        $this->comment = '';

        // 成功メッセージを表示
        Flux::toast(variant: 'success', text: __('approval-flow::detail.messages.rejected'));
        $this->admin = false;

        // 最新のタスク情報をリロード
        $this->task = $this->task->fresh()->load('user', 'histories.user', 'flow');
    }



    public function render()
    {
        return view('approval-flow::livewire.approval-flow.detail');
    }
}
