<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::with(['activeRoleAssignments.organizationNode'])->find($id);
    }

    public function findByUsername(string $username): ?User
    {
        return User::with(['activeRoleAssignments.organizationNode'])
            ->where('username', $username)
            ->first();
    }

    public function getAllPaginated(
        int $perPage = 50,
        ?string $search = null,
        ?string $status = null,
        ?string $role = null,
        ?int $organizationNodeId = null,
        string $sort = 'latest',
        ?array $allowedNodeIds = null
    ): LengthAwarePaginator {
        $query = User::with(['activeRoleAssignments.organizationNode']);

        if ($allowedNodeIds !== null) {
            $query->where(function ($q) use ($allowedNodeIds) {
                $q->whereHas('activeRoleAssignments', function ($sq) use ($allowedNodeIds) {
                    $sq->whereIn('organization_node_id', $allowedNodeIds);
                })->orDoesntHave('activeRoleAssignments');
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($role) {
            if ($role === 'none' || $role === 'without_role') {
                $query->doesntHave('activeRoleAssignments');
            } else {
                $query->whereHas('activeRoleAssignments', function ($q) use ($role) {
                    $q->where('role', $role);
                });
            }
        }

        if ($organizationNodeId) {
            $query->whereHas('activeRoleAssignments', function ($q) use ($organizationNodeId) {
                $q->where('organization_node_id', $organizationNodeId);
            });
        }

        if ($sort === 'oldest') {
            $query->orderBy('id', 'asc');
        } elseif ($sort === 'name_asc') {
            $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc');
        } elseif ($sort === 'name_desc') {
            $query->orderBy('first_name', 'desc')->orderBy('last_name', 'desc');
        } else {
            $query->orderBy('id', 'desc');
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }
}
