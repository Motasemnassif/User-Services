<?php

declare(strict_types=1);

namespace Src\Domain\UseCases;

use Src\Domain\Exceptions\InvalidCredentialsException;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\ValueObjects\Email;

final class LoginUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(string $email, string $password): array
    {
        $emailValueObject = new Email($email);
        $user = $this->userRepository->findByEmail($emailValueObject);

        if ($user === null) {
            throw new InvalidCredentialsException('Invalid email or password');
        }

        if (!password_verify($password, $user->getPassword())) {
            throw new InvalidCredentialsException('Invalid email or password');
        }

        return [
            'user' => $user->toArray(),
            'message' => 'Login successful',
        ];
    }
}
