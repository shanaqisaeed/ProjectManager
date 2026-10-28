<?php

namespace App\Services;

use App\Enums\PermissionAccessType;
use App\Enums\ProjectMemberStatus;
use App\Enums\ProjectPermission;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMemberPermission;
use App\Models\ProjectRolePermission;
use App\Models\User;

class ProjectPermissionResolver
{
    public function __construct(
        private readonly AuthorizationService $authorizationService
    ) {}

    /**
     * Central Permission Resolution Logic.
     * Evaluates permission for a user on a given project based on priority hierarchy:
     * 1. SUPER_ADMIN
     * 2. Explicit Direct DENY on current project
     * 3. Explicit Direct ALLOW on current project
     * 4. Project Role Permission (ACCEPTED member)
     * 5. Parent Project Inherited Permission
     * 6. Organizational Scope Authority
     * 7. Default Deny
     */
    public function hasPermission(User $user, Project $project, ProjectPermission|string $permission): bool
    {
        $permString = $permission instanceof ProjectPermission ? $permission->value : $permission;

        // 1. Check SUPER_ADMIN & Global Admin
        if ($this->authorizationService->isSuperAdmin($user) || $this->authorizationService->isGlobalAdminOrManager($user)) {
            return true;
        }

        // 2 & 3. Check Direct Overrides on current project
        $directOverride = ProjectMemberPermission::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('permission', $permString)
            ->first();

        if ($directOverride) {
            if ($directOverride->access_type === PermissionAccessType::DENY) {
                return false; // Explicit DENY wins over everything below
            }
            if ($directOverride->access_type === PermissionAccessType::ALLOW) {
                return true;
            }
        }

        // 4. Check Project Role Permission on current project
        $member = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('status', ProjectMemberStatus::ACCEPTED->value)
            ->first();

        if ($member) {
            $hasRolePermission = ProjectRolePermission::where('project_role', $member->project_role->value)
                ->where('permission', $permString)
                ->exists();

            if ($hasRolePermission) {
                return true;
            }
        }

        // 5. Check Parent Project Inherited Permission (Recursively)
        if ($project->parent_project_id) {
            $parentProject = $project->parent;
            if ($parentProject && $this->hasInheritedPermissionFromParent($user, $parentProject, $permString)) {
                return true;
            }
        }

        // 6. Check Organizational Scope Authority
        // Organizational managers (ORGANIZATION_MANAGER, UNIT_MANAGER, TEAM_LEAD, etc.) who have scope authority over project node OR project creator
        $hasNodeAuthority = $project->organization_node_id && $this->authorizationService->canAccessNode($user, $project->organization_node_id);
        $hasCreatorAuthority = $project->creator && $this->authorizationService->canManageTargetUser($user, $project->creator);

        if ($hasNodeAuthority || $hasCreatorAuthority) {
            // Check if user holds a managerial system role
            if ($user->isExecutiveOrAdmin() || $user->isProjectOrUnitManager()) {
                return true;
            }
        }

        // 7. Default Deny
        return false;
    }

    /**
     * Recursively checks parent project for permission inheritance.
     */
    private function hasInheritedPermissionFromParent(User $user, Project $parentProject, string $permString, array $visitedProjectIds = []): bool
    {
        if (in_array($parentProject->id, $visitedProjectIds)) {
            return false; // Circular protection
        }
        $visitedProjectIds[] = $parentProject->id;

        // Parent direct DENY wins over parent inheritance
        $parentDirectOverride = ProjectMemberPermission::where('project_id', $parentProject->id)
            ->where('user_id', $user->id)
            ->where('permission', $permString)
            ->first();

        if ($parentDirectOverride) {
            if ($parentDirectOverride->access_type === PermissionAccessType::DENY) {
                return false;
            }
            if ($parentDirectOverride->access_type === PermissionAccessType::ALLOW) {
                return true;
            }
        }

        // Parent Role Permission
        $parentMember = ProjectMember::where('project_id', $parentProject->id)
            ->where('user_id', $user->id)
            ->where('status', ProjectMemberStatus::ACCEPTED->value)
            ->first();

        if ($parentMember) {
            $hasParentRolePerm = ProjectRolePermission::where('project_role', $parentMember->project_role->value)
                ->where('permission', $permString)
                ->exists();

            if ($hasParentRolePerm) {
                return true;
            }
        }

        // Parent's parent
        if ($parentProject->parent_project_id && $parentProject->parent) {
            return $this->hasInheritedPermissionFromParent($user, $parentProject->parent, $permString, $visitedProjectIds);
        }

        return false;
    }
}
