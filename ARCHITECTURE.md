# Architecture Overview

## Clean Architecture Layers

### 1. Domain Layer (`src/Domain/`)

The core business logic layer, independent of frameworks and external concerns.

#### Entities
- `User`: Core user entity with business rules

#### Value Objects
- `UserId`: Immutable user identifier
- `UserName`: User name with validation
- `Email`: Email address with validation

#### Use Cases
- `CreateUserUseCase`: Creates a new user and publishes event
- `UpdateUserUseCase`: Updates existing user
- `DeleteUserUseCase`: Deletes a user
- `GetUserUseCase`: Retrieves a single user
- `ListUsersUseCase`: Lists users with pagination
- `LoginUserUseCase`: Authenticates user credentials

#### Repositories (Interfaces)
- `UserRepositoryInterface`: Contract for user data access

#### Services (Interfaces)
- `EventPublisherInterface`: Contract for event publishing
- `PaymentGatewayInterface`: Contract for payment processing

#### Events
- `UserCreatedEvent`: Domain event fired when user is created

#### Exceptions
- `UserNotFoundException`: User not found (404)
- `UserAlreadyExistsException`: User already exists (409)
- `InvalidCredentialsException`: Invalid login credentials (401)

### 2. Application Layer (`src/Application/`)

Application-specific DTOs and data structures.

#### DTOs
- `CreateUserRequest`: Request data for user creation
- `UpdateUserRequest`: Request data for user update
- `LoginRequest`: Request data for login

### 3. Infrastructure Layer (`src/Infrastructure/`)

Implementation of domain interfaces using external libraries and frameworks.

#### Repositories
- `EloquentUserRepository`: Eloquent ORM implementation of UserRepositoryInterface

#### Adapters
- `RabbitMQEventPublisher`: RabbitMQ implementation of EventPublisherInterface

#### Gateways
- `PaymentGateway`: HTTP client implementation of PaymentGatewayInterface

### 4. Delivery Layer (`app/Http/`)

HTTP layer handling requests and responses.

#### Controllers
- `UserController`: Handles user CRUD operations
- `AuthController`: Handles authentication (login, logout, me)

#### Routes
- `routes/api.php`: API route definitions

## Data Flow

### Creating a User

1. **Request** → `UserController::store()`
2. **Validation** → Request validation
3. **DTO** → `CreateUserRequest` created
4. **Use Case** → `CreateUserUseCase::execute()`
5. **Repository** → `EloquentUserRepository::save()`
6. **Event** → `UserCreatedEvent` published via `RabbitMQEventPublisher`
7. **Response** → JSON response with user data

### Authentication Flow

1. **Request** → `AuthController::login()`
2. **Validation** → Request validation
3. **DTO** → `LoginRequest` created
4. **Use Case** → `LoginUserUseCase::execute()`
5. **Repository** → `EloquentUserRepository::findByEmail()`
6. **Token** → Passport token generated
7. **Response** → JSON response with token

## Dependency Injection

All dependencies are registered in `AppServiceProvider`:

```php
UserRepositoryInterface → EloquentUserRepository
EventPublisherInterface → RabbitMQEventPublisher
PaymentGatewayInterface → PaymentGateway
```

## Error Handling

- Domain exceptions are caught and converted to appropriate HTTP responses
- Custom `Handler` class handles all exceptions consistently
- All errors follow a standard JSON format

## Type Safety

- All files use `declare(strict_types=1)`
- All methods have type hints for parameters and return types
- Value objects ensure type safety at the domain level

## PSR Standards

- PSR-4: Autoloading standard
- PSR-12: Coding style standard
- PSR-7: HTTP message interfaces (via Laravel)

