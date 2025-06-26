<div>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('approval-flow::task-list.title') }}</h1>
                <div>

                </div>
            </div>

            <!-- フィルターセクション -->
            <div class="mt-4 bg-white p-4 rounded-md shadow">
                <div class="flex flex-wrap gap-4">
                    <div>
                        <label for="flowId" class="block text-sm font-medium text-gray-700">{{ __('approval-flow::task-list.flow') }}</label>
                        <select wire:model.live="flowId" id="flowId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">{{ __('approval-flow::task-list.all') }}</option>
                            @foreach($flows as $flow)
                                <option value="{{ $flow->id }}">{{ $flow->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">{{ __('approval-flow::task-list.status_label') }}</label>
                        <select wire:model.live="status" id="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">{{ __('approval-flow::task-list.all') }}</option>
                            <option value="pending">{{ __('approval-flow::detail.status.pending') }}</option>
                            <option value="approved">{{ __('approval-flow::detail.status.approved') }}</option>
                            <option value="rejected">{{ __('approval-flow::detail.status.rejected') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">{{ __('approval-flow::task-list.search') }}</label>
                        <input wire:model.live.debounce.300ms="search" type="text" id="search" placeholder="{{ __('approval-flow::task-list.search_placeholder') }}" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    </div>
                    <div class="flex items-end">
                        <button wire:click="resetFilters" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            {{ __('approval-flow::task-list.reset') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-6 bg-white shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sort('id')">
                                    ID
                                    @if ($sortBy === 'id')
                                        @if ($sortDirection === 'asc')
                                            <span>↑</span>
                                        @else
                                            <span>↓</span>
                                        @endif
                                    @endif
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('approval-flow::task-list.flow') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('approval-flow::task-list.applicant') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sort('status')">
                                    {{ __('approval-flow::task-list.status_column') }}
                                    @if ($sortBy === 'status')
                                        @if ($sortDirection === 'asc')
                                            <span>↑</span>
                                        @else
                                            <span>↓</span>
                                        @endif
                                    @endif
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sort('is_complete')">
                                    {{ __('approval-flow::task-list.completed') }}
                                    @if ($sortBy === 'is_complete')
                                        @if ($sortDirection === 'asc')
                                            <span>↑</span>
                                        @else
                                            <span>↓</span>
                                        @endif
                                    @endif
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sort('created_at')">
                                    {{ __('approval-flow::task-list.created_at') }}
                                    @if ($sortBy === 'created_at')
                                        @if ($sortDirection === 'asc')
                                            <span>↑</span>
                                        @else
                                            <span>↓</span>
                                        @endif
                                    @endif
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('approval-flow::task-list.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($this->tasks as $task)
                                <tr wire:key="task-{{ $task->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $task->id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $task->flow->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $task->user->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            {{ $task->status == 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $task->status == 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $task->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        ">
                                            {{__('approval-flow::detail.status.'.$task->status)}}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="{{ $task->is_complete ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $task->is_complete ? __('approval-flow::task-list.completed_status') : __('approval-flow::task-list.not_completed') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $task->created_at->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex space-x-2">
                                            <a href="" wire:click="detail({{$task->id}})" class="text-indigo-600 hover:text-indigo-900">{{ __('approval-flow::task-list.details') }}</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">{{ __('approval-flow::task-list.no_tasks_found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4">
                    {{ $this->tasks->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
