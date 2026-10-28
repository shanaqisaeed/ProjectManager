<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByUsername(string $username): ?User;

    public function getAllPaginated(
        int $perPage = 50,
        ?string $search = null,
        ?string $status = null,
        ?string $role = null,
        ?int $organizationNodeId = null,
        string $sort = 'latest',
        ?array $allowedNodeIds = null
    ): LengthAwarePaginator;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function delete(User $user): bool;
}
