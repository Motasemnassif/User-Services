<?php

declare(strict_types=1);

namespace Src\Domain\UseCases;

use Src\Domain\Entities\User;
use Src\Domain\Exceptions\UserNotFoundException;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\ValueObjects\UserId;

final class GetUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(int $userId): User
    {
        $userIdValueObject = new UserId($userId);
        $user = $this->userRepository->findById($userIdValueObject);

        if ($user === null) {
            throw new UserNotFoundException("User with ID {$userId} not found");
        }

        return $user;
    }
}
