<?php

namespace Lastdino\ApprovalFlow\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalFlow extends Model
{
    protected $table = 'approval_flows';

    protected $fillable = [
        'name',
        'description',
        'flow', // JSON構造 (Drawflowなど)
        'version',
    ];

    protected $casts = [
        'flow' => 'array',
    ];

    /**
     * このフローを使ったタスク
     */
    public function tasks()
    {
        return $this->hasMany(ApprovalFlowTask::class, 'workflow_id');
    }
}
