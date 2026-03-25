<?php

use Lastdino\ApprovalFlow\Models\ApprovalFlowTask;
use Lastdino\ApprovalFlow\Services\ApprovalFlowService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Flux\Flux;

new class extends Component
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

        // 既に承認または拒否されている場合の制御
        $nodeHistories = $this->task->histories->where('node_id', $this->node);

        // 現在ノードが Resolver の場合は、チェーン継続中の「過去の承認」が存在しても次の承認者が操作できるようにする。
        $flowData = $this->task->flow->flow ?? [];
        $isResolver = ($flowData['drawflow']['Home']['data'][$this->node]['name'] ?? null) === 'resolver';

        if ($isResolver) {
            // Resolver ノードでは「却下」またはタスク完了時のみブロック
            if ($nodeHistories->contains('name', 'Rejected') || $this->task->is_complete) {
                $this->admin = false;
            }
        } else {
            // 従来ノードでは承認/却下/完了でブロック
            if ($nodeHistories->contains('name', 'Approved') || $nodeHistories->contains('name', 'Rejected') || $this->task->is_complete) {
                $this->admin = false;
            }
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
}; ?>

<div class="w-full">

    <div class="shadow p-2 mt-2 rounded flex flex-col bg-white dark:bg-slate-800 border border-gray-300 dark:border-gray-700">



        {{-- 内容 --}}
        <div class="mt-2 flex flex-col gap-4">
            <div>
                <flux:heading size="xl">{{ $task->flow->name ?? '-' }}</flux:heading>
                <flux:text>{{__('approval-flow::detail.labels.updated_at')}}:{{$task->updated_at->format(config('approval-flow.datetime.formats.date'))}}</flux:text>
                <flux:text>{{__('approval-flow::detail.labels.status')}}:{{__('approval-flow::detail.status.'. $task->status)}}</flux:text>
                <flux:text>{{__('approval-flow::detail.labels.applicant')}}:{{ \Lastdino\ApprovalFlow\Helpers\UserDisplayHelper::getDisplayName($task->user) }}
                </flux:text>
            </div>

            {{-- 申請詳細 --}}
            <div class="">
                <flux:heading class="">{{__('approval-flow::detail.labels.details')}}</flux:heading>
                <flux:text class="">{{$task->comment}}</flux:text>
                @if($task->link)
                    <a href="{{ $task->link }}" class="text-blue-500 hover:text-blue-700 underline" target="_blank">
                        {{__('approval-flow::detail.link.related_link')}}
                    </a>
                @endif

            </div>
            {{-- 添付ファイル --}}
            <div class="">
                <flux:heading class="">{{__('approval-flow::detail.labels.attachments')}}</flux:heading>
                <div class="border border-gray-300 dark:border-gray-700 rounded mt-3">
                    <div class="p-2">
                        @if($admin)
                            @foreach($file as $f)
                                <div class="flex items-center group">
                                    <a href="{{ asset('files/' . $f['path']) }}" download="{{ $f['name'] }}"
                                       class="text-gray-900 transition group-hover:text-blue-300">
                                        <i class="far fa-file-alt text-gray-400 mr-1 group-hover:text-blue-300"></i>
                                        {{ $f['name'] }}
                                    </a>
                                </div>
                            @endforeach
                        @else
                            <flux:text>{{__('approval-flow::detail.messages.no_permission')}}</flux:text>
                        @endif
                    </div>
                </div>
            </div>

            {{-- コメント履歴 --}}
            <div class="">
                <flux:heading class="">{{__('approval-flow::detail.labels.comments')}}</flux:heading>
                <div class="border border-gray-300 dark:border-gray-700 rounded mt-3">
                    <div class="p-2 h-56 overflow-y-auto">
                        @foreach($task->histories as $history)
                            <div>
                                <div class="flex gap-2">
                                    <flux:text>{{$history->created_at->format(config('approval-flow.datetime.formats.date'))}}</flux:text>
                                    <flux:separator vertical />
                                    <flux:text>{{ \Lastdino\ApprovalFlow\Helpers\UserDisplayHelper::getDisplayName($history->user) }}</flux:text>
                                    <flux:separator vertical />
                                    <flux:text>{{__('approval-flow::detail.comments.'.$history->name)}}</flux:text>
                                </div>
                                <flux:text class="ml-3 whitespace-pre-wrap">{{ $history->comment }}</flux:text>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>



        {{-- ボタン --}}
        <div class="flex justify-end mt-3 gap-2">
            @if($node && $post && $admin)
                <flux:modal.trigger name="approve-modal">
                    <flux:button variant="primary" >{{__('approval-flow::detail.buttons.approve')}}</flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="reject-modal">
                    <flux:button variant="danger" >{{__('approval-flow::detail.buttons.reject')}}</flux:button>
                </flux:modal.trigger>

            @endif
        </div>
    </div>

    <flux:modal name="approve-modal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">承認登録</flux:heading>
            </div>
            <flux:textarea
                label="{{__('approval-flow::detail.labels.comments')}}"
                placeholder="{{__('approval-flow::detail.messages.please_comment')}}"
                wire:model="comment"
            />
            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary" wire:click="approve">{{__('approval-flow::detail.buttons.save')}}</flux:button>
            </div>
        </div>
    </flux:modal>
    <flux:modal name="reject-modal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">却下登録</flux:heading>
            </div>
            <flux:textarea
                label="{{__('approval-flow::detail.labels.comments')}}"
                placeholder="{{__('approval-flow::detail.messages.please_comment')}}"
                wire:model="comment"
            />
            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary" wire:click="reject">{{__('approval-flow::detail.buttons.save')}}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
