<?php

namespace App\Services;

use App\Contracts\UserRepositoryInterface;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function getUsers(
        int $perPage = 50,
        ?string $search = null,
        ?string $status = null,
        ?string $role = null,
        ?int $organizationNodeId = null,
        string $sort = 'latest',
        ?array $allowedNodeIds = null
    ): LengthAwarePaginator {
        return $this->userRepository->getAllPaginated($perPage, $search, $status, $role, $organizationNodeId, $sort, $allowedNodeIds);
    }

    public function createUser(array $data): User
    {
        $data['password_hash'] = Hash::make($data['password']);
        unset($data['password']);

        if (! isset($data['status'])) {
            $data['status'] = UserStatus::ACTIVE->value;
        }

        return $this->userRepository->create($data);
    }

    public function updateUserStatus(User $user, UserStatus $status): User
    {
        return $this->userRepository->update($user, ['status' => $status->value]);
    }

    public function updateUser(User $user, array $data): User
    {
        if (! empty($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
            unset($data['password']);
        } else {
            unset($data['password']);
        }

        return $this->userRepository->update($user, $data);
    }
}
