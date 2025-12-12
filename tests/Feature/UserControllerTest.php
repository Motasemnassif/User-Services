<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Src\Domain\Services\EventPublisherInterface;
use Tests\TestCase;

final class UserControllerTest extends TestCase
{

    /**
     * Test creating a new user with data verification.
     */
    public function test_create_user_with_data_verification(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePassword123!',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User created successfully',
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                ],
            ]);

        // Verify user was saved in database
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        // Verify password is hashed
        $createdUser = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertNotEquals('SecurePassword123!', $createdUser->password);
        $this->assertTrue(Hash::check('SecurePassword123!', $createdUser->password));
    }

    /**
     * Test creating user with invalid data returns validation errors.
     */
    public function test_create_user_with_invalid_data(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $invalidData = [
            'name' => '', // Empty name
            'email' => 'invalid-email', // Invalid email format
            'password' => '123', // Password too short
        ];

        $response = $this->postJson('/api/users', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ]);
    }

    /**
     * Test creating user with duplicate email returns error.
     */
    public function test_create_user_with_duplicate_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $authenticatedUser = User::factory()->create();
        Passport::actingAs($authenticatedUser);

        $userData = [
            'name' => 'New User',
            'email' => 'existing@example.com', // Duplicate email
            'password' => 'Password123!',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'User with email existing@example.com already exists',
            ]);
    }

    /**
     * Test rejecting invalid token returns 401 Unauthorized.
     */
    public function test_reject_invalid_token_returns_401(): void
    {
        // Make request without authentication
        $response = $this->getJson('/api/users');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'message',
            ]);
    }

    /**
     * Test rejecting invalid token format returns 401.
     */
    public function test_reject_invalid_token_format_returns_401(): void
    {
        // Make request with invalid token format
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token-format-12345',
        ])->getJson('/api/users');

        $response->assertStatus(401);
    }

    /**
     * Test rejecting expired token returns 401.
     */
    public function test_reject_expired_token_returns_401(): void
    {
        $user = User::factory()->create();
        
        // Create a token and then manually expire it
        $token = $user->createToken('test-token');
        
        // Revoke the token to simulate expiration
        $token->token->revoke();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->accessToken,
        ])->getJson('/api/users');

        $response->assertStatus(401);
    }

    /**
     * Test RabbitMQ event is published when user is created using mocking.
     */
    public function test_rabbitmq_event_published_when_user_created(): void
    {
        // Mock the EventPublisherInterface
        $eventPublisherMock = $this->mock(EventPublisherInterface::class);
        
        // Expect the publish method to be called once with specific parameters
        $eventPublisherMock->expects($this->once())
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

        // Bind the mock to the service container
        $this->app->instance(EventPublisherInterface::class, $eventPublisherMock);

        $user = User::factory()->create();
        Passport::actingAs($user);

        $userData = [
            'name' => 'Event Test User',
            'email' => 'event.test@example.com',
            'password' => 'Password123!',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201);
        
        // The mock will verify that publish was called
    }

    /**
     * Test updating non-existent user returns 404 error.
     */
    public function test_update_nonexistent_user_returns_404(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $nonExistentUserId = 99999;

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->putJson("/api/users/{$nonExistentUserId}", $updateData);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => "User with ID {$nonExistentUserId} not found",
            ]);
    }

    /**
     * Test updating existing user successfully.
     */
    public function test_update_existing_user_successfully(): void
    {
        $authenticatedUser = User::factory()->create();
        Passport::actingAs($authenticatedUser);

        $userToUpdate = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->putJson("/api/users/{$userToUpdate->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => [
                    'id' => $userToUpdate->id,
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                ],
            ]);

        // Verify user was updated in database
        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    /**
     * Test deleting non-existent user returns 404 error.
     */
    public function test_delete_nonexistent_user_returns_404(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $nonExistentUserId = 99999;

        $response = $this->deleteJson("/api/users/{$nonExistentUserId}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => "User with ID {$nonExistentUserId} not found",
            ]);
    }

    /**
     * Test getting non-existent user returns 404 error.
     */
    public function test_get_nonexistent_user_returns_404(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $nonExistentUserId = 99999;

        $response = $this->getJson("/api/users/{$nonExistentUserId}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => "User with ID {$nonExistentUserId} not found",
            ]);
    }

    /**
     * Test listing users requires authentication.
     */
    public function test_list_users_requires_authentication(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(401);
    }

    /**
     * Test listing users with valid token returns users.
     */
    public function test_list_users_with_valid_token(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        // Create some test users
        User::factory()->count(5)->create();

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'page',
                    'per_page',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }
}

