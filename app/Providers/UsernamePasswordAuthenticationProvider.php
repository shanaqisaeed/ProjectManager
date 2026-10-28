<?php

namespace App\Providers;

use App\Contracts\AuthenticationProviderInterface;
use App\Contracts\UserRepositoryInterface;
use App\DTOs\AuthenticatedUserDto;
use App\DTOs\CredentialsDto;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UsernamePasswordAuthenticationProvider implements AuthenticationProviderInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function authenticate(CredentialsDto $credentials): AuthenticatedUserDto
    {
        $genericErrorMessage = 'نام کاربری یا رمز عبور اشتباه است.';

        $user = $this->userRepository->findByUsername($credentials->username);

        if (! $user) {
            throw ValidationException::withMessages([
                'username' => [$genericErrorMessage],
            ]);
        }

        if (! Hash::check($credentials->password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'username' => [$genericErrorMessage],
            ]);
        }

        if (! $user->status->canLogin()) {
            throw ValidationException::withMessages([
                'username' => [$genericErrorMessage],
            ]);
        }

        $roles = $user->activeRoleAssignments->pluck('role.value')->toArray();
        $primaryAssignment = $user->primaryRoleAssignment;

        return new AuthenticatedUserDto(
            user: $user,
            roles: $roles,
            primaryOrganizationNodeId: $primaryAssignment?->organization_node_id
        );
    }
}
