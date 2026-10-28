<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSupervisor extends Model
{
    use HasFactory;

    protected $table = 'task_supervisors';

    protected $fillable = [
        'task_id',
        'project_approval_stage_id',
        'stage_number',
        'stage_name',
        'user_id',
        'status',
        'comments',
        'action_at',
    ];

    protected function casts(): array
    {
        return [
            'stage_number' => 'integer',
            'action_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function approvalStage(): BelongsTo
    {
        return $this->belongsTo(ProjectApprovalStage::class, 'project_approval_stage_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
