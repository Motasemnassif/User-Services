<?php

declare(strict_types=1);

namespace Src\Domain\UseCases;

use Src\Domain\Entities\User;
use Src\Domain\Events\UserCreatedEvent;
use Src\Domain\Exceptions\UserAlreadyExistsException;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\Services\EventPublisherInterface;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\UserId;
use Src\Domain\ValueObjects\UserName;

final class CreateUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EventPublisherInterface $eventPublisher
    ) {}

    public function execute(
        string $name,
        string $email,
        string $password
    ): User {
        $emailValueObject = new Email($email);

        if ($this->userRepository->findByEmail($emailValueObject) !== null) {
            throw new UserAlreadyExistsException("User with email {$email} already exists");
        }

        $userId = new UserId($this->userRepository->getNextId());
        $userName = new UserName($name);
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $user = new User(
            id: $userId,
            name: $userName,
            email: $emailValueObject,
            password: $hashedPassword,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable()
        );

        $savedUser = $this->userRepository->save($user);

        // $event = new UserCreatedEvent($savedUser, new \DateTimeImmutable());
        // $this->eventPublisher->publish('user.created', $event->toArray());

        return $savedUser;
    }
}
