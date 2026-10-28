<?php

namespace App\Models;

use App\Enums\TaskPriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTemplateTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'project_template_tasks';

    protected $fillable = [
        'project_template_id',
        'parent_task_id',
        'title',
        'description',
        'priority',
        'weight',
        'offset_days',
        'duration_days',
        'executor_user_id',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'weight' => 'integer',
            'offset_days' => 'integer',
            'duration_days' => 'integer',
        ];
    }

    public function projectTemplate(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'project_template_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateTask::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(ProjectTemplateTask::class, 'parent_task_id')->orderBy('offset_days');
    }

    public function children(): HasMany
    {
        return $this->subtasks();
    }

    public function childrenRecursive(): HasMany
    {
        return $this->subtasks()->with(['childrenRecursive', 'executor', 'supervisors.user']);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_user_id');
    }

    public function supervisors(): HasMany
    {
        return $this->hasMany(ProjectTemplateTaskSupervisor::class, 'project_template_task_id')->orderBy('stage_number');
    }
}
