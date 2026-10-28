<?php

namespace App\DTOs;

use App\Models\User;

readonly class AuthenticatedUserDto
{
    public function __construct(
        public User $user,
        public array $roles,
        public ?int $primaryOrganizationNodeId = null
    ) {}
}
