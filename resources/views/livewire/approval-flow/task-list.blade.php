<div>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <flux:heading size="xl">{{ __('approval-flow::task-list.title') }}</flux:heading>
                <div>

                </div>
            </div>

            <!-- フィルターセクション -->
            <div class="mt-4 bg-white dark:bg-slate-800 p-4 rounded-md shadow border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap items-end gap-4">
                    <flux:select wire:model.live="flowId" label="{{ __('approval-flow::task-list.flow') }}">
                        <flux:select.option value="">{{ __('approval-flow::task-list.all') }}</flux:select.option>
                        @foreach($flows as $flow)
                            <flux:select.option value="{{ $flow->id }}">{{ $flow->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="status" label="{{ __('approval-flow::task-list.status_label') }}">
                        <flux:select.option value="">{{ __('approval-flow::task-list.all') }}</flux:select.option>
                        <flux:select.option value="pending">{{ __('approval-flow::detail.status.pending') }}</flux:select.option>
                        <flux:select.option value="approved">{{ __('approval-flow::detail.status.approved') }}</flux:select.option>
                        <flux:select.option value="rejected">{{ __('approval-flow::detail.status.rejected') }}</flux:select.option>
                    </flux:select>

                    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('approval-flow::task-list.search_placeholder') }}" label="{{ __('approval-flow::task-list.search') }}" />

                    <flux:button wire:click="resetFilters">
                        {{ __('approval-flow::task-list.reset') }}
                    </flux:button>
                </div>
            </div>

            <div class="mt-6 bg-white dark:bg-slate-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
                        <flux:table.column>{{ __('approval-flow::task-list.flow') }}</flux:table.column>
                        <flux:table.column>{{ __('approval-flow::task-list.applicant') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">{{ __('approval-flow::task-list.status_column') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'is_complete'" :direction="$sortDirection" wire:click="sort('is_complete')">{{ __('approval-flow::task-list.completed') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('approval-flow::task-list.created_at') }}</flux:table.column>
                        <flux:table.column>{{ __('approval-flow::task-list.actions') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->tasks as $task)
                            <flux:table.row wire:key="task-{{ $task->id }}">
                                <flux:table.cell>{{ $task->id }}</flux:table.cell>
                                <flux:table.cell class="font-medium text-gray-900 dark:text-white">{{ $task->flow->name }}</flux:table.cell>
                                <flux:table.cell>{{ $task->user->name ?? 'N/A' }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="match($task->status) {
                                        'approved' => 'green',
                                        'rejected' => 'red',
                                        'pending' => 'yellow',
                                        default => 'slate',
                                    }" inset="top bottom">
                                        {{__('approval-flow::detail.status.'.$task->status)}}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$task->is_complete ? 'green' : 'red'" variant="subtle">
                                        {{ $task->is_complete ? __('approval-flow::task-list.completed_status') : __('approval-flow::task-list.not_completed') }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>{{ $task->created_at->format('Y-m-d') }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:button variant="ghost" size="sm" icon="eye" wire:click="detail({{$task->id}})">
                                        {{ __('approval-flow::task-list.details') }}
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="text-center">{{ __('approval-flow::task-list.no_tasks_found') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>

                <div class="px-6 py-4">
                    {{ $this->tasks->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
