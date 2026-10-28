<?php

namespace App\Services;

use App\Contracts\UserRoleAssignmentRepositoryInterface;
use App\Enums\OrganizationNodeType;
use App\Enums\RoleAssignmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserRoleAssignment;

class AuthorizationService
{
    public function __construct(
        private readonly UserRoleAssignmentRepositoryInterface $assignmentRepository
    ) {}

    public function assignRoleToUser(int $userId, string $role, ?int $organizationNodeId = null, bool $isPrimary = false)
    {
        $assignment = $this->assignmentRepository->assignRole($userId, $role, $organizationNodeId, $isPrimary);

        $user = User::find($userId);
        if ($user && $user->status === UserStatus::INACTIVE) {
            $user->update(['status' => UserStatus::ACTIVE->value]);
        }

        return $assignment;
    }

    public function removeRoleAssignment(int $assignmentId): bool
    {
        $assignment = UserRoleAssignment::find($assignmentId);
        if (! $assignment) {
            return false;
        }

        $userId = $assignment->user_id;
        $deleted = $this->assignmentRepository->removeAssignment($assignmentId);

        if ($deleted && $userId) {
            $remainingCount = UserRoleAssignment::where('user_id', $userId)
                ->where('status', RoleAssignmentStatus::ACTIVE->value)
                ->count();

            if ($remainingCount === 0) {
                User::where('id', $userId)->update(['status' => UserStatus::INACTIVE->value]);
            }
        }

        return $deleted;
    }

    public function hasRole(User $user, UserRole $role): bool
    {
        return $user->activeRoleAssignments->contains(fn ($assignment) => $assignment->role === $role);
    }

    public function isSuperAdmin(User $user): bool
    {
        return $this->hasRole($user, UserRole::SUPER_ADMIN);
    }

    public function isGlobalAdminOrManager(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->activeRoleAssignments->contains(function ($assignment) {
            return $assignment->role === UserRole::ORGANIZATION_MANAGER &&
                (is_null($assignment->organization_node_id) || $assignment->organizationNode?->type === OrganizationNodeType::ORGANIZATION);
        });
    }

    public function canManageTargetUser(User $currentUser, User $targetUser, ?array $allowedNodeIds = null): bool
    {
        if ($this->isGlobalAdminOrManager($currentUser)) {
            return true;
        }

        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        $targetNodeIds = $targetUser->activeRoleAssignments
            ->pluck('organization_node_id')
            ->filter()
            ->toArray();

        if (empty($targetNodeIds)) {
            return true;
        }

        if ($allowedNodeIds !== null) {
            foreach ($targetNodeIds as $nodeId) {
                if (in_array($nodeId, $allowedNodeIds)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($targetNodeIds as $nodeId) {
            if ($this->canAccessNode($currentUser, $nodeId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the user is authorized for a specific organization node or its descendants.
     */
    public function canAccessNode(User $user, ?int $targetNodeId): bool
    {
        if (! $targetNodeId) {
            return true;
        }

        if ($this->isGlobalAdminOrManager($user)) {
            return true;
        }

        foreach ($user->activeRoleAssignments as $assignment) {
            if (! $assignment->organization_node_id) {
                return true;
            }

            if ($assignment->organization_node_id === $targetNodeId) {
                return true;
            }

            if ($assignment->organizationNode) {
                $descendants = $assignment->organizationNode->getAllDescendantIds();
                if (in_array($targetNodeId, $descendants)) {
                    return true;
                }
            }
        }

        return false;
    }
}
