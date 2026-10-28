<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tasks';

    protected $fillable = [
        'project_id',
        'parent_id',
        'title',
        'description',
        'executor_user_id',
        'priority',
        'weight',
        'status',
        'start_date',
        'due_date',
        'version',
        'created_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'weight' => 'integer',
            'status' => TaskStatus::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('due_date', 'asc');
    }

    public function children(): HasMany
    {
        return $this->subtasks();
    }

    public function childrenRecursive(): HasMany
    {
        return $this->subtasks()->with(['childrenRecursive', 'executor', 'creator']);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function taskSupervisors(): HasMany
    {
        return $this->hasMany(TaskSupervisor::class, 'task_id');
    }

    public function supervisors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_supervisors', 'task_id', 'user_id')
            ->withPivot(['status', 'comments', 'action_at'])
            ->withTimestamps();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class, 'task_id')->orderBy('created_at', 'desc');
    }

    public function chats(): HasMany
    {
        return $this->hasMany(TaskChat::class, 'task_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get task deadline status alert indicator.
     */
    public function getDeadlineStatusAttribute(): array
    {
        if ($this->status === TaskStatus::COMPLETED) {
            return [
                'status_key' => 'completed',
                'label' => 'تکمیل شده',
                'badge' => '✅',
                'bg_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/80 dark:text-emerald-300',
            ];
        }

        if (! $this->due_date) {
            return [
                'status_key' => 'normal',
                'label' => 'بدون موعد',
                'badge' => '🟢',
                'bg_class' => 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300',
            ];
        }

        $now = now()->startOfDay();
        $dueDate = $this->due_date->startOfDay();

        if ($now->greaterThan($dueDate)) {
            $daysOverdue = (int) $dueDate->diffInDays($now);

            return [
                'status_key' => 'overdue',
                'label' => "مهلت گذشته ({$daysOverdue} روز تاخیر)",
                'badge' => '⛔',
                'bg_class' => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950 dark:text-rose-300 animate-pulse',
            ];
        }

        $daysRemaining = (int) $now->diffInDays($dueDate);

        if ($daysRemaining <= 2) {
            return [
                'status_key' => 'urgent',
                'label' => 'فوری ('.($daysRemaining === 0 ? 'امروز' : "{$daysRemaining} روز باقی‌مانده").')',
                'badge' => '🔴',
                'bg_class' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/80 dark:text-red-300',
            ];
        }

        if ($daysRemaining <= 7) {
            return [
                'status_key' => 'warning',
                'label' => "نزدیک به موعد ({$daysRemaining} روز باقی‌مانده)",
                'badge' => '🟡',
                'bg_class' => 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-950/80 dark:text-amber-300',
            ];
        }

        return [
            'status_key' => 'normal',
            'label' => "زمان کافی ({$daysRemaining} روز باقی‌مانده)",
            'badge' => '🟢',
            'bg_class' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/80 dark:text-emerald-300',
        ];
    }

    /**
     * Check if task is a descendant of given potential parent ID to prevent circular references.
     */
    public function isDescendantOf(int $potentialParentId): bool
    {
        if ($this->id === $potentialParentId) {
            return true;
        }

        $current = Task::find($potentialParentId);
        $visited = [$potentialParentId];

        while ($current && $current->parent_id) {
            if ($current->parent_id === $this->id) {
                return true;
            }
            if (in_array($current->parent_id, $visited)) {
                break; // Protection against existing circular refs
            }
            $visited[] = $current->parent_id;
            $current = $current->parent;
        }

        return false;
    }

    /**
     * Check if all assigned supervisors have approved the task.
     */
    public function allSupervisorsApproved(): bool
    {
        $supervisors = $this->taskSupervisors;
        if ($supervisors->isEmpty()) {
            return true;
        }

        return $supervisors->every(fn (TaskSupervisor $ts) => $ts->status === 'APPROVED');
    }

    /**
     * Check if any supervisor has rejected the task.
     */
    public function hasRejectedSupervisor(): bool
    {
        return $this->taskSupervisors->contains(fn (TaskSupervisor $ts) => $ts->status === 'REJECTED');
    }

    /**
     * Get unresolved subtasks (neither COMPLETED nor CANCELLED).
     */
    public function getUnresolvedSubtasks(): Collection
    {
        return $this->subtasks()
            ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value])
            ->get();
    }

    /**
     * Check if task has any unresolved subtasks (direct or descendant).
     */
    public function hasUnresolvedSubtasks(): bool
    {
        $directUnresolved = $this->subtasks()
            ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value])
            ->exists();

        if ($directUnresolved) {
            return true;
        }

        foreach ($this->subtasks()->get() as $subtask) {
            if ($subtask->hasUnresolvedSubtasks()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if task can be marked as COMPLETED.
     */
    public function canBeCompleted(): bool
    {
        return ! $this->hasUnresolvedSubtasks() && $this->allSupervisorsApproved();
    }
}
