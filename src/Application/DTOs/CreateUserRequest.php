<?php

declare(strict_types=1);

namespace Src\Application\DTOs;

final class CreateUserRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}
}
