<?php

namespace App\Services;

use App\Contracts\AuthenticationProviderInterface;
use App\DTOs\AuthenticatedUserDto;
use App\DTOs\CredentialsDto;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function __construct(
        private readonly AuthenticationProviderInterface $authProvider
    ) {}

    public function login(CredentialsDto $credentials): AuthenticatedUserDto
    {
        $authenticatedUser = $this->authProvider->authenticate($credentials);

        Auth::login($authenticatedUser->user, $credentials->remember);
        request()->session()->regenerate();

        return $authenticatedUser;
    }

    public function logout(): void
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
