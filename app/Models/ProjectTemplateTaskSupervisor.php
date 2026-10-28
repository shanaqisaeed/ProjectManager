<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTemplateTaskSupervisor extends Model
{
    use HasFactory;

    protected $table = 'project_template_task_supervisors';

    protected $fillable = [
        'project_template_task_id',
        'stage_number',
        'stage_name',
        'user_id',
    ];

    public function templateTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateTask::class, 'project_template_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
