<?php

declare(strict_types=1);

namespace Src\Application\DTOs;

final class LoginRequest
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}
}
