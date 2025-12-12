<?php

declare(strict_types=1);

namespace Src\Domain\Services;

interface EventPublisherInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function publish(string $eventType, array $data): void;
}
