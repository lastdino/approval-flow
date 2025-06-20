<?php

namespace Lastdino\ApprovalFlow\Livewire\ApprovalFlow;

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Lastdino\ApprovalFlow\Models\ApprovalFlow;

class Edit extends Component
{
    #[Url]
    public $flow_id;
    #[Validate('required')]
    public $name;
    public $flow_data=[];
    //役職リスト
    public $PostList=[];
    //ユーザーリスト
    public $UserList=[];

    public function saveFlow($data){
        $validated=$this->validate();
        $validated['flow']=$data;
        ApprovalFlow::updateOrCreate(
            ['id'=>$this->flow_id],$validated
        );

        Flux::toast(variant: 'success', text: __('Successfully registered.'),);
    }

    public function mount(){
        if($this->flow_id){
            $db=ApprovalFlow::find($this->flow_id);
            $this->name=$db->name;
            $this->flow_data=$db->flow_data;
        }
        $userModel=config('approval-flow.users_model');
        if(!class_exists($userModel)){
            alert("`user_model` not configured");
        }
        $rolesModel=config('approval-flow.roles_model');
        if(!class_exists($rolesModel)){
            alert("`roles_model` not configured");
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
