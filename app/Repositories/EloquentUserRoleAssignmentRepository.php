<?php

namespace App\Repositories;

use App\Contracts\UserRoleAssignmentRepositoryInterface;
use App\Enums\RoleAssignmentStatus;
use App\Models\UserRoleAssignment;
use Illuminate\Database\Eloquent\Collection;

class EloquentUserRoleAssignmentRepository implements UserRoleAssignmentRepositoryInterface
{
    public function getActiveForUser(int $userId): Collection
    {
        return UserRoleAssignment::with('organizationNode')
            ->where('user_id', $userId)
            ->where('status', RoleAssignmentStatus::ACTIVE->value)
            ->get();
    }

    public function assignRole(int $userId, string $role, ?int $organizationNodeId = null, bool $isPrimary = false): UserRoleAssignment
    {
        if ($isPrimary) {
            UserRoleAssignment::where('user_id', $userId)->update(['is_primary' => false]);
        }

        return UserRoleAssignment::create([
            'user_id' => $userId,
            'role' => $role,
            'organization_node_id' => $organizationNodeId,
            'is_primary' => $isPrimary,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);
    }

    public function removeAssignment(int $assignmentId): bool
    {
        $assignment = UserRoleAssignment::find($assignmentId);

        return $assignment ? $assignment->delete() : false;
    }

    public function setPrimaryRole(int $userId, int $assignmentId): void
    {
        UserRoleAssignment::where('user_id', $userId)->update(['is_primary' => false]);
        UserRoleAssignment::where('id', $assignmentId)->where('user_id', $userId)->update(['is_primary' => true]);
    }
}
