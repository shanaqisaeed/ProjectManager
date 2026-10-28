<?php

namespace App\Models;

use App\Enums\ProjectPriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'project_templates';

    protected $fillable = [
        'parent_template_id',
        'organization_node_id',
        'title',
        'description',
        'priority',
        'offset_days',
        'duration_days',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'priority' => ProjectPriority::class,
            'offset_days' => 'integer',
            'duration_days' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'parent_template_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProjectTemplate::class, 'parent_template_id');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with(['childrenRecursive', 'organizationNode', 'templateRoles.user', 'approvalStages.supervisor', 'tasks.supervisors.user']);
    }

    public function organizationNode(): BelongsTo
    {
        return $this->belongsTo(OrganizationNode::class, 'organization_node_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function templateRoles(): HasMany
    {
        return $this->hasMany(ProjectTemplateRole::class, 'project_template_id');
    }

    public function approvalStages(): HasMany
    {
        return $this->hasMany(ProjectTemplateApprovalStage::class, 'project_template_id')->orderBy('stage_number');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTemplateTask::class, 'project_template_id');
    }

    public function rootTasks(): HasMany
    {
        return $this->hasMany(ProjectTemplateTask::class, 'project_template_id')->whereNull('parent_task_id')->orderBy('offset_days');
    }

    /**
     * Recursively get all descendant sub-template IDs.
     */
    public function getAllDescendantIds(): array
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getAllDescendantIds());
        }

        return $ids;
    }
}
