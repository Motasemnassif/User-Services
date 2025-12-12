<?php

declare(strict_types=1);

namespace Src\Domain\UseCases;

use Src\Domain\Entities\User;
use Src\Domain\Exceptions\UserNotFoundException;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\UserId;
use Src\Domain\ValueObjects\UserName;

final class UpdateUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(
        int $userId,
        ?string $name = null,
        ?string $email = null,
        ?string $password = null
    ): User {
        $userIdValueObject = new UserId($userId);
        $user = $this->userRepository->findById($userIdValueObject);

        if ($user === null) {
            throw new UserNotFoundException("User with ID {$userId} not found");
        }

        if ($name !== null) {
            $user->setName(new UserName($name));
        }

        if ($email !== null) {
            $emailValueObject = new Email($email);
            $existingUser = $this->userRepository->findByEmail($emailValueObject);
            if ($existingUser !== null && !$existingUser->getId()->equals($userIdValueObject)) {
                throw new \InvalidArgumentException("Email {$email} is already taken");
            }
            $user->setEmail($emailValueObject);
        }

        if ($password !== null) {
            $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
        }

        $user->setUpdatedAt(new \DateTimeImmutable());

        return $this->userRepository->save($user);
    }
}
