<?php

declare(strict_types=1);

namespace Src\Domain\Repositories;

use Src\Domain\Entities\User;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\UserId;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    /**
     * @return array<User>
     */
    public function findAll(int $page = 1, int $perPage = 15): array;

    public function save(User $user): User;

    public function delete(UserId $id): void;

    public function getNextId(): int;
}
