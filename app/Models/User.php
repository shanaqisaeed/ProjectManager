<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'password_hash',
        'email',
        'mobile',
        'status',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
        ];
    }

    /**
     * Override default password column name for Laravel Auth.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getDisplayTitleAttribute(): string
    {
        $name = $this->full_name;

        // Try primary assignment first, then any active assignment
        $assignment = $this->primaryRoleAssignment ?? $this->activeRoleAssignments->first();

        if (! $assignment) {
            return "{$name} (بدون نقش)";
        }

        $roleLabel = $assignment->role ? $assignment->role->label() : '';
        $nodeName = $assignment->organizationNode ? $assignment->organizationNode->name : 'کل سازمان';

        if ($nodeName === 'کل سازمان') {
            return "{$name} - {$roleLabel}";
        }

        return "{$name} - {$roleLabel} ({$nodeName})";
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(UserRoleAssignment::class, 'user_id');
    }

    public function activeRoleAssignments(): HasMany
    {
        return $this->hasMany(UserRoleAssignment::class, 'user_id')
            ->where('status', 'ACTIVE');
    }

    public function primaryRoleAssignment()
    {
        return $this->hasOne(UserRoleAssignment::class, 'user_id')
            ->where('status', 'ACTIVE')
            ->where('is_primary', true);
    }

    public function hasRole(UserRole|string $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role : UserRole::tryFrom($role);

        return $this->activeRoleAssignments->contains(fn ($assignment) => $assignment->role === $roleValue);
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SUPER_ADMIN);
    }

    public function isOrganizationManager(): bool
    {
        return $this->hasRole(UserRole::ORGANIZATION_MANAGER);
    }

    public function isExecutiveOrAdmin(): bool
    {
        return $this->hasAnyRole([
            UserRole::SUPER_ADMIN,
            UserRole::ORGANIZATION_MANAGER,
        ]);
    }

    public function isProjectOrUnitManager(): bool
    {
        return $this->hasAnyRole([
            UserRole::PROJECT_MANAGER,
            UserRole::UNIT_MANAGER,
            UserRole::TEAM_LEAD,
        ]);
    }

    public function canCreateProjects(): bool
    {
        return $this->hasAnyRole([
            UserRole::SUPER_ADMIN,
            UserRole::ORGANIZATION_MANAGER,
            UserRole::UNIT_MANAGER,
            UserRole::TEAM_LEAD,
        ]);
    }

    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin() || $this->isExecutiveOrAdmin() || $this->isProjectOrUnitManager();
    }

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'user_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members', 'user_id', 'project_id')
            ->withPivot(['project_role', 'status', 'invited_by', 'approved_by', 'joined_at'])
            ->withTimestamps();
    }
}
