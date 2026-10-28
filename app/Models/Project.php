<?php

namespace App\Models;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'parent_project_id',
        'organization_node_id',
        'title',
        'project_key',
        'description',
        'start_date',
        'deadline',
        'status',
        'priority',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'priority' => ProjectPriority::class,
            'start_date' => 'date',
            'deadline' => 'date',
        ];
    }

    public function getTimelineProgressAttribute(): int
    {
        if (! $this->start_date || ! $this->deadline) {
            return 0;
        }

        $start = $this->start_date->timestamp;
        $end = $this->deadline->timestamp;
        $now = now()->timestamp;

        if ($now <= $start) {
            return 0;
        }

        $totalSeconds = $end - $start;
        if ($totalSeconds <= 0) {
            return 100;
        }

        $elapsedSeconds = $now - $start;

        return (int) round(($elapsedSeconds / $totalSeconds) * 100);
    }

    public function getDeadlineAlertAttribute(): ?array
    {
        if ($this->status === ProjectStatus::COMPLETED || $this->status === ProjectStatus::ARCHIVED || ! $this->deadline) {
            return null;
        }

        $now = now()->startOfDay();
        $deadline = $this->deadline->startOfDay();

        if ($now->greaterThan($deadline)) {
            $daysPassed = $deadline->diffInDays($now);

            return [
                'type' => 'overdue',
                'label' => "🚨 مهلت زمانی پروژه به پایان رسیده است! ({$daysPassed} روز تاخیر)",
                'class' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/80 dark:text-rose-300 dark:border-rose-800 animate-pulse',
            ];
        }

        $progress = $this->timeline_progress;
        if ($progress >= 80) {
            return [
                'type' => 'warning',
                'label' => "⚠️ بیش از ۸۰٪ زمان پروژه طی شده ({$progress}٪)، انجامش دادی؟",
                'class' => 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-950/80 dark:text-amber-300 dark:border-amber-700',
            ];
        }

        return null;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'parent_project_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Project::class, 'parent_project_id');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with(['childrenRecursive', 'organizationNode', 'creator', 'projectManagers.user']);
    }

    public function organizationNode(): BelongsTo
    {
        return $this->belongsTo(OrganizationNode::class, 'organization_node_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'project_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')
            ->withPivot(['project_role', 'status', 'invited_by', 'approved_by', 'joined_at'])
            ->withTimestamps();
    }

    public function projectManagers(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'project_id')
            ->where('project_role', 'PROJECT_MANAGER')
            ->where('status', 'ACCEPTED');
    }

    public function memberPermissions(): HasMany
    {
        return $this->hasMany(ProjectMemberPermission::class, 'project_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    public function getTasksProgressAttribute(): int
    {
        $allProjectIds = array_merge([$this->id], $this->getAllDescendantIds());
        $total = Task::whereIn('project_id', $allProjectIds)->count();
        if ($total === 0) {
            return 0;
        }

        $completed = Task::whereIn('project_id', $allProjectIds)->where('status', TaskStatus::COMPLETED->value)->count();

        return (int) round(($completed / $total) * 100);
    }

    public function approvalStages(): HasMany
    {
        return $this->hasMany(ProjectApprovalStage::class, 'project_id')->orderBy('stage_number');
    }

    /**
     * Recursively get all descendant subproject IDs.
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

    /**
     * Get array of ancestor project IDs up to the root project.
     */
    public function getAncestorIds(): array
    {
        $ancestors = [];
        $current = $this->parent;

        while ($current) {
            if (in_array($current->id, $ancestors)) {
                break; // Protect against circular loops
            }
            $ancestors[] = $current->id;
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Get Collection of ancestor models from root down to parent.
     */
    public function getAncestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            if ($ancestors->contains('id', $current->id)) {
                break;
            }
            $ancestors->prepend($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Check if project is a descendant of given potential parent ID to prevent circular references.
     */
    public function isDescendantOf(int $potentialParentId): bool
    {
        if ($this->id === $potentialParentId) {
            return true;
        }

        $descendants = $this->getAllDescendantIds();

        return in_array($potentialParentId, $descendants);
    }
}
