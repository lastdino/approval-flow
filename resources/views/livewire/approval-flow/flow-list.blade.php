<div>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <flux:heading size="xl">{{ __('approval-flow::flow.list_title') }}</flux:heading>
                <flux:button variant="primary" icon="plus" wire:click="save">
                    {{ __('approval-flow::flow.new_create') }}
                </flux:button>
            </div>

            <!-- 検索バー -->
            <div class="mt-4">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('approval-flow::flow.search') }}" />
            </div>

            <div class="mt-6 bg-white dark:bg-slate-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">{{ __('approval-flow::flow.id') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">{{ __('approval-flow::flow.name') }}</flux:table.column>
                        <flux:table.column>{{ __('approval-flow::flow.description') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'version'" :direction="$sortDirection" wire:click="sort('version')">{{ __('approval-flow::flow.version') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('approval-flow::flow.created_at') }}</flux:table.column>
                        <flux:table.column>{{ __('approval-flow::flow.actions') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->flows as $flow)
                            <flux:table.row wire:key="flow-{{ $flow->id }}">
                                <flux:table.cell>{{ $flow->id }}</flux:table.cell>
                                <flux:table.cell class="font-medium text-gray-900 dark:text-white">{{ $flow->name }}</flux:table.cell>
                                <flux:table.cell>{{ Str::limit($flow->description, 50) }}</flux:table.cell>
                                <flux:table.cell>{{ $flow->version }}</flux:table.cell>
                                <flux:table.cell>{{ $flow->created_at->format('Y-m-d') }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex space-x-2">
                                        <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="setting({{ $flow->id }})">
                                            {{ __('approval-flow::flow.edit') }}
                                        </flux:button>
                                        <flux:button variant="ghost" size="sm" icon="list-bullet" wire:click="tasks({{ $flow->id }})">
                                            {{ __('approval-flow::flow.task_list') }}
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="text-center">{{ __('approval-flow::flow.not_found') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>

                <div class="px-6 py-4">
                    {{ $this->flows->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
