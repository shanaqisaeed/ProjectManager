<?php

namespace App\Contracts;

use App\DTOs\AuthenticatedUserDto;
use App\DTOs\CredentialsDto;

interface AuthenticationProviderInterface
{
    /**
     * Authenticate credentials and return authenticated user DTO.
     * Throws \Illuminate\Auth\AuthenticationException on failure with standard generic message.
     */
    public function authenticate(CredentialsDto $credentials): AuthenticatedUserDto;
}
