<?php

namespace Lastdino\ApprovalFlow\Livewire\ApprovalFlow;

use Livewire\Attributes\Url;
use Livewire\Component;
use Lastdino\ApprovalFlow\Models\ApprovalFlowTask;
use Lastdino\ApprovalFlow\Models\ApprovalFlow;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

class TaskList extends Component
{
    use WithPagination;

    #[Url]
    public $flowId = null;
    #[Url]
    public $status = null;


    public $search = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';


    public function mount($flowId = null)
    {
        $this->flowId = $flowId;
    }

    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function detail($id){
        $this->redirectRoute(config('approval-flow.routes.prefix'). '.detail', ['task' => $id]);
    }

    #[Computed]
    public function tasks()
    {
        $perPage = config('approval-flow.pagination.task_list_per_page', 25);
        return ApprovalFlowTask::query()
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->tap(fn ($query) => $this->flowId ? $query->where('flow_id', $this->flowId) : $query)
            ->tap(fn ($query) => $this->status ? $query->where('status', $this->status) : $query)
            ->tap(fn ($query) => $this->search ? $query->with(['flow', 'user'])->where(function($q) {
                $q->whereHas('flow', function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })->orWhereHas('user', function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            }) : $query)
            ->paginate($perPage);
    }

    public function render()
    {
        $flows = ApprovalFlow::all();

        return view('approval-flow::livewire.approval-flow.task-list', [
            'flows' => $flows
        ]);
    }

    public function resetFilters()
    {
        $this->reset(['search', 'flowId', 'status']);
    }
}
