<?php

declare(strict_types=1);

namespace Src\Domain\ValueObjects;

final class UserName
{
    public function __construct(
        private readonly string $value
    ) {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('User name cannot be empty');
        }

        if (strlen($value) > 255) {
            throw new \InvalidArgumentException('User name cannot exceed 255 characters');
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(UserName $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
