<?php

declare(strict_types=1);

namespace Src\Domain\UseCases;

use Src\Domain\Exceptions\UserNotFoundException;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\ValueObjects\UserId;

final class DeleteUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(int $userId): void
    {
        $userIdValueObject = new UserId($userId);
        $user = $this->userRepository->findById($userIdValueObject);

        if ($user === null) {
            throw new UserNotFoundException("User with ID {$userId} not found");
        }

        $this->userRepository->delete($userIdValueObject);
    }
}
