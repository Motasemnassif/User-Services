<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Src\Application\DTOs\CreateUserRequest;
use Src\Application\DTOs\UpdateUserRequest;
use Src\Domain\Exceptions\InvalidCredentialsException;
use Src\Domain\Exceptions\UserAlreadyExistsException;
use Src\Domain\Exceptions\UserNotFoundException;
use Src\Domain\UseCases\CreateUserUseCase;
use Src\Domain\UseCases\DeleteUserUseCase;
use Src\Domain\UseCases\GetUserUseCase;
use Src\Domain\UseCases\ListUsersUseCase;
use Src\Domain\UseCases\UpdateUserUseCase;

final class UserController extends Controller
{
    public function __construct(
        private readonly CreateUserUseCase $createUserUseCase,
        private readonly UpdateUserUseCase $updateUserUseCase,
        private readonly DeleteUserUseCase $deleteUserUseCase,
        private readonly GetUserUseCase $getUserUseCase,
        private readonly ListUsersUseCase $listUsersUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 15);

            $users = $this->listUsersUseCase->execute($page, $perPage);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($user) => $user->toArray(), $users),
                'meta' => [
                    'page' => $page,
                    'per_page' => $perPage,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:8',
            ]);

            $dto = new CreateUserRequest(
                name: $validated['name'],
                email: $validated['email'],
                password: $validated['password'],
            );

            $user = $this->createUserUseCase->execute(
                $dto->name,
                $dto->email,
                $dto->password
            );

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user->toArray(),
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (UserAlreadyExistsException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->getUserUseCase->execute($id);

            return response()->json([
                'success' => true,
                'data' => $user->toArray(),
            ]);
        } catch (UserNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255',
                'password' => 'sometimes|string|min:8',
            ]);

            $dto = new UpdateUserRequest(
                name: $validated['name'] ?? null,
                email: $validated['email'] ?? null,
                password: $validated['password'] ?? null,
            );

            $user = $this->updateUserUseCase->execute(
                $id,
                $dto->name,
                $dto->email,
                $dto->password
            );

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user->toArray(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (UserNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->deleteUserUseCase->execute($id);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (UserNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
