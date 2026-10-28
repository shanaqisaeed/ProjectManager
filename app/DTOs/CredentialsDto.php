<?php

namespace App\DTOs;

readonly class CredentialsDto
{
    public function __construct(
        public string $username,
        public string $password,
        public bool $remember = false
    ) {}
}
