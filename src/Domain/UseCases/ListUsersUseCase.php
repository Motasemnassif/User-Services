<?php

declare(strict_types=1);

namespace Src\Domain\UseCases;

use Src\Domain\Entities\User;
use Src\Domain\Repositories\UserRepositoryInterface;

final class ListUsersUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * @return array<User>
     */
    public function execute(int $page = 1, int $perPage = 15): array
    {
        return $this->userRepository->findAll($page, $perPage);
    }
}
