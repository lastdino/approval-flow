<?php

namespace Lastdino\ApprovalFlow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalFlowTask extends Model
{
    protected $table = 'approval_flow_tasks';

    protected $fillable = [
        'workflow_id',
        'user_id',
        'ref_id',
        'system_type',
        'status',
        'is_complete',
        'node_id',
        'comment',
        'msg',
        'link',
    ];

    protected $casts = [
        'is_complete' => 'boolean',
    ];

    /**
     * このタスクが属するフロー定義
     */
    public function flow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'workflow_id');
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
        return $this->hasMany(ApprovalFlowHistory::class, 'workflow_task_id');
    }
}
