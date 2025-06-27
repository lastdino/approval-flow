<?php

namespace Lastdino\ApprovalFlow\Livewire\ApprovalFlow;

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use Lastdino\ApprovalFlow\Models\ApprovalFlow;
use Flux\Flux;

class Edit extends Component
{
    #[Url]
    public $flow_id;
    #[Validate('required')]
    public $name;
    #[Validate('nullable')]
    public $description;
    #[Validate('nullable')]
    public $version;
    public $flow=[];
    //役職リスト
    public $PostList=[];
    //ユーザーリスト
    public $UserList=[];

    public function save($data){
        $validated=$this->validate();
        $validated['flow']=$data;
        $validated['version']=$validated['version']+1;
        ApprovalFlow::updateOrCreate(
            ['id'=>$this->flow_id],$validated
        );

        Flux::toast(variant: 'success', text: __('Successfully registered.'),);
    }

    public function mount(){
        if($this->flow_id){
            $db=ApprovalFlow::find($this->flow_id);
            $this->name=$db->name;
            $this->description=$db->description;
            $this->version=$db->version;
            $this->flow=$db->flow;
        }
        $userModel=config('approval-flow.users_model');
        if(!class_exists($userModel)){
            Flux::toast(variant: 'danger', text:'user_model` not configured');
        }
        $rolesModel=config('approval-flow.roles_model');
        if(!class_exists($rolesModel)){
            Flux::toast(variant: 'danger', text:'roles_model` not configured');
        }
        $this->UserList=($userModel)::query()->where('enrollment',true)->get();
        $this->PostList=($rolesModel)::query()->get();
        //$this->PostList=Role::where('authority',true)->get();
    }

    public function render()
    {
        return view('approval-flow::livewire.approval-flow.edit');
    }
}
