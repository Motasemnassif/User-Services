<?php

declare(strict_types=1);

namespace Src\Domain\Events;

use Src\Domain\Entities\User;

final class UserCreatedEvent
{
    public function __construct(
        private readonly User $user,
        private readonly \DateTimeImmutable $occurredOn
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function toArray(): array
    {
        return [
            'event_type' => 'user.created',
            'user' => $this->user->toArray(),
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s'),
        ];
    }
}
