<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use Src\Domain\Entities\User;
use Src\Domain\Events\UserCreatedEvent;
use Src\Domain\Exceptions\UserAlreadyExistsException;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\Services\EventPublisherInterface;
use Src\Domain\UseCases\CreateUserUseCase;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\UserId;
use Src\Domain\ValueObjects\UserName;
use Tests\TestCase;

final class CreateUserUseCaseTest extends TestCase
{
    private MockObject $userRepositoryMock;
    private MockObject $eventPublisherMock;
    private CreateUserUseCase $createUserUseCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->eventPublisherMock = $this->createMock(EventPublisherInterface::class);
        
        $this->createUserUseCase = new CreateUserUseCase(
            $this->userRepositoryMock,
            $this->eventPublisherMock
        );
    }

    /**
     * Test creating a user successfully publishes event.
     */
    public function test_create_user_successfully_publishes_event(): void
    {
        $email = new Email('test@example.com');
        $userId = new UserId(1);
        
        // Mock repository to return null (user doesn't exist)
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($this->equalTo($email))
            ->willReturn(null);

        // Mock repository to return next ID
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('getNextId')
            ->willReturn(1);

        // Create expected user entity
        $expectedUser = new User(
            id: $userId,
            name: new UserName('Test User'),
            email: $email,
            password: password_hash('password123', PASSWORD_BCRYPT),
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable()
        );

        // Mock repository save method
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->willReturn($expectedUser);

        // Expect event publisher to be called
        $this->eventPublisherMock
            ->expects($this->once())
            ->method('publish')
            ->with(
                $this->equalTo('user.created'),
                $this->callback(function (array $data) {
                    return isset($data['event_type']) 
                        && $data['event_type'] === 'user.created'
                        && isset($data['user'])
                        && isset($data['occurred_on']);
                })
            );

        $result = $this->createUserUseCase->execute(
            'Test User',
            'test@example.com',
            'password123'
        );

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('Test User', $result->getName()->getValue());
        $this->assertEquals('test@example.com', $result->getEmail()->getValue());
    }

    /**
     * Test creating user with existing email throws exception.
     */
    public function test_create_user_with_existing_email_throws_exception(): void
    {
        $email = new Email('existing@example.com');
        $existingUser = new User(
            id: new UserId(1),
            name: new UserName('Existing User'),
            email: $email,
            password: 'hashed',
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable()
        );

        // Mock repository to return existing user
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($this->equalTo($email))
            ->willReturn($existingUser);

        // Event publisher should not be called
        $this->eventPublisherMock
            ->expects($this->never())
            ->method('publish');

        $this->expectException(UserAlreadyExistsException::class);
        $this->expectExceptionMessage('User with email existing@example.com already exists');

        $this->createUserUseCase->execute(
            'New User',
            'existing@example.com',
            'password123'
        );
    }
}

