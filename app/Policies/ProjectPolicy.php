<?php

namespace App\Policies;

use App\Enums\ProjectPermission;
use App\Models\Project;
use App\Models\User;
use App\Services\AuthorizationService;
use App\Services\ProjectPermissionResolver;
use App\Services\ProjectService;

class ProjectPolicy
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly ProjectPermissionResolver $permissionResolver,
        private readonly ProjectService $projectService
    ) {}

    public function before(User $user, string $ability): ?bool
    {
        if ($this->authorizationService->isSuperAdmin($user)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $this->permissionResolver->hasPermission($user, $project, ProjectPermission::VIEW_PROJECT);
    }

    public function create(User $user, ?int $organizationNodeId = null): bool
    {
        if (! $user->canCreateProjects()) {
            return false;
        }

        if (! $organizationNodeId) {
            return true;
        }

        return $this->projectService->canUserCreateProjectInNode($user, $organizationNodeId);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->permissionResolver->hasPermission($user, $project, ProjectPermission::UPDATE_PROJECT);
    }

    public function delete(User $user, Project $project): bool
    {
        // Project deletion is strictly restricted to SUPER_ADMIN & ORGANIZATION_MANAGER
        if (! $user->isExecutiveOrAdmin()) {
            return false;
        }

        return $this->permissionResolver->hasPermission($user, $project, ProjectPermission::DELETE_PROJECT);
    }

    public function archive(User $user, Project $project): bool
    {
        if (! $user->canCreateProjects()) {
            return false;
        }

        return $this->permissionResolver->hasPermission($user, $project, ProjectPermission::ARCHIVE_PROJECT);
    }

    public function createSubProject(User $user, Project $project): bool
    {
        return $this->permissionResolver->hasPermission($user, $project, ProjectPermission::CREATE_SUBPROJECT);
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_MEMBERS);
    }

    public function inviteMembers(User $user, Project $project): bool
    {
        return $this->permissionResolver->hasPermission($user, $project, ProjectPermission::INVITE_MEMBERS) ||
            $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_MEMBERS);
    }

    public function removeMembers(User $user, Project $project): bool
    {
        return $this->permissionResolver->hasPermission($user, $project, ProjectPermission::REMOVE_MEMBERS) ||
            $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_MEMBERS);
    }

    public function managePermissions(User $user, Project $project): bool
    {
        return $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_MEMBERS);
    }

    public function saveAsTemplate(User $user, Project $project): bool
    {
        return $this->permissionResolver->hasPermission($user, $project, ProjectPermission::CREATE_TEMPLATE) ||
            $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_TEMPLATES);
    }

    public function createTemplate(User $user, Project $project): bool
    {
        return $this->saveAsTemplate($user, $project);
    }

    public function manageTemplates(User $user, Project $project): bool
    {
        return $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_TEMPLATES) ||
            $this->permissionResolver->hasPermission($user, $project, ProjectPermission::CREATE_TEMPLATE);
    }
}
