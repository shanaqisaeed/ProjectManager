<?php

namespace App\Contracts;

use App\Models\UserRoleAssignment;
use Illuminate\Database\Eloquent\Collection;

interface UserRoleAssignmentRepositoryInterface
{
    public function getActiveForUser(int $userId): Collection;

    public function assignRole(int $userId, string $role, ?int $organizationNodeId = null, bool $isPrimary = false): UserRoleAssignment;

    public function removeAssignment(int $assignmentId): bool;

    public function setPrimaryRole(int $userId, int $assignmentId): void;
}
