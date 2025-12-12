<?php

declare(strict_types=1);

namespace Src\Application\DTOs;

final class UpdateUserRequest
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
    ) {}
}
