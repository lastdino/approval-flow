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
                                <flux:text class="ml-3">{{ $history->comment }}</flux:text>
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
