<?php

namespace Lastdino\ApprovalFlow\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalFlowHistory extends Model
{
    protected $table = 'approval_flow_histories';

    protected $fillable = [
        'flow_task_id',
        'node_id',
        'user_id',
        'name',     // "申請", "承認", "却下" など
        'comment',
    ];

    /**
     * タスクとの関連
     */
    public function task()
    {
        return $this->belongsTo(ApprovalFlowTask::class, 'flow_task_id');
    }

    /**
     * アクションを実行したユーザー
     */
    public function user()
    {
        return $this->belongsTo(config('approval-flow.users_model'), 'user_id');
    }
}
