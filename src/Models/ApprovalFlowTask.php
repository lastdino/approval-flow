<?php

namespace Lastdino\ApprovalFlow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalFlowTask extends Model
{
    protected $table = 'approval_flow_tasks';

    protected $fillable = [
        'flow_id',
        'user_id',
        'ref_id',
        'system_type',
        'status',
        'is_complete',
        'node_id',
        'comment',
        'system_roles',
        'msg',
        'link',
    ];

    protected $casts = [
        'is_complete' => 'boolean',
        'system_roles' => 'array',
    ];

    /**
     * このタスクが属するフロー定義
     */
    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'flow_id');
    }

    /**
     * ターゲットモデル（申請対象）
     */
    public function target(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'system_type', 'ref_id');
    }

    /**
     * 申請ユーザー
     */
    public function user()
    {
        return $this->belongsTo(config('approval-flow.users_model'), 'user_id');
    }

    /**
     * 履歴一覧
     */
    public function histories()
    {
        return $this->hasMany(ApprovalFlowHistory::class, 'flow_task_id');
    }
}
