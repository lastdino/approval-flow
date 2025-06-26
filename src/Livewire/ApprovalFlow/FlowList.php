<?php

namespace Lastdino\ApprovalFlow\Livewire\ApprovalFlow;

use Livewire\Component;
use Lastdino\ApprovalFlow\Models\ApprovalFlow;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

class FlowList extends Component
{
    use WithPagination;


    public $search = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function setting($id)
    {
        $this->redirectRoute(config('approval-flow.routes.prefix'). '.edit', ['flow_id' => $id]);
    }
    public function tasks($id)
    {
        $this->redirectRoute(config('approval-flow.routes.prefix'). '.task_list', ['flowId' => $id]);
    }

    #[Computed]
    public function flows()
    {
        $perPage = config('approval-flow.pagination.flow_list_per_page', 25);

        return ApprovalFlow::query()
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->tap(fn ($query) => $this->search ? $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('description', 'like', '%' . $this->search . '%') : $query)
            ->paginate( $perPage);
    }


    public function render()
    {
        return view('approval-flow::livewire.approval-flow.flow-list');
    }
}
