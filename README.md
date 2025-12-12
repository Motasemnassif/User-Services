# User Service - Microservices Architecture

A Laravel-based User Service microservice implementing Clean Architecture (Domain-Driven Design) with OAuth2 authentication and RabbitMQ event publishing.

## Architecture

This project follows Clean Architecture principles with the following layers:

### Domain Layer (`src/Domain/`)

-   **Entities**: Core business entities (User)
-   **Value Objects**: Immutable value objects (UserId, UserName, Email)
-   **Use Cases**: Business logic (CreateUser, UpdateUser, DeleteUser, GetUser, ListUsers, LoginUser)
-   **Repositories**: Repository interfaces (contracts)
-   **Services**: Service interfaces (EventPublisher, PaymentGateway)
-   **Events**: Domain events (UserCreatedEvent)
-   **Exceptions**: Domain-specific exceptions

### Application Layer (`src/Application/`)

-   **DTOs**: Data Transfer Objects for requests

### Infrastructure Layer (`src/Infrastructure/`)

-   **Repositories**: Eloquent-based repository implementations
-   **Adapters**: External service adapters (RabbitMQEventPublisher)
-   **Gateways**: External service gateways (PaymentGateway)

### Delivery Layer (`app/Http/`)

-   **Controllers**: API controllers (UserController, AuthController)
-   **Routes**: API route definitions

## Features

-   ✅ **CRUD Operations**: Full Create, Read, Update, Delete for users
-   ✅ **OAuth2 Authentication**: Laravel Passport integration
-   ✅ **Event Publishing**: RabbitMQ integration for user creation events
-   ✅ **Payment Gateway**: Integration interface for payment service
-   ✅ **Error Handling**: Comprehensive exception handling with proper HTTP status codes
-   ✅ **Type Safety**: Strict types and type hints throughout
-   ✅ **PSR Standards**: Follows PSR-12 coding standards

## Installation

1. Install dependencies:

```bash
composer install
```

2. Copy environment file:

```bash
cp .env.example .env
```

3. Generate application key:

```bash
php artisan key:generate
```

4. Configure database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. Configure RabbitMQ in `.env`:

```env
RABBITMQ_HOST=localhost
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
RABBITMQ_EXCHANGE=user_events
```

6. Configure Payment Service in `.env`:

```env
PAYMENT_SERVICE_BASE_URL=https://api.payment-service.com
PAYMENT_SERVICE_API_KEY=your_api_key
```

7. Run migrations:

```bash
php artisan migrate
```

8. Install Passport:

```bash
php artisan passport:install
```

## API Endpoints

### Authentication

#### Login

```http
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com"
        },
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
        "token_type": "Bearer"
    }
}
```

#### Logout

```http
POST /api/logout
Authorization: Bearer {access_token}
```

#### Get Current User

```http
GET /api/me
Authorization: Bearer {access_token}
```

### User Management

#### List Users

```http
GET /api/users?page=1&per_page=15
Authorization: Bearer {access_token}
```

#### Create User

```http
POST /api/users
Content-Type: application/json
Authorization: Bearer {access_token}

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
}
```

**Note:** This will publish a `user.created` event to RabbitMQ.

#### Get User

```http
GET /api/users/{id}
Authorization: Bearer {access_token}
```

#### Update User

```http
PUT /api/users/{id}
Content-Type: application/json
Authorization: Bearer {access_token}

{
    "name": "Jane Doe",
    "email": "jane@example.com"
}
```

#### Delete User

```http
DELETE /api/users/{id}
Authorization: Bearer {access_token}
```

## Event Publishing

When a new user is created, a `user.created` event is automatically published to RabbitMQ with the following structure:

```json
{
    "event_type": "user.created",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": null,
        "created_at": "2024-01-01 12:00:00",
        "updated_at": "2024-01-01 12:00:00"
    },
    "occurred_on": "2024-01-01 12:00:00"
}
```

## Error Handling

The service includes comprehensive error handling:

-   **UserNotFoundException** (404): User not found
-   **UserAlreadyExistsException** (409): User already exists
-   **InvalidCredentialsException** (401): Invalid login credentials
-   **ValidationException** (422): Request validation failed
-   **Generic Exceptions** (500): Internal server errors

All errors follow a consistent format:

```json
{
    "success": false,
    "message": "Error message",
    "errors": {} // Only for validation errors
}
```

## Testing

Run tests:

```bash
php artisan test
```

## Distributed User/Wallet Saga (Outbox + Eventual Consistency)

- User creation and outbox insert happen in one local transaction. If user creation fails, no `user.created` event is emitted → no wallet attempt.
- An outbox dispatcher publishes `user.created` to RabbitMQ.
- Wallet Service subscribes, attempts wallet creation, and emits `wallet.created` or `wallet.create_failed` via its own outbox.
- User Service listens to wallet outcomes:
  - `wallet.created` → updates user.wallet_status = created.
  - `wallet.create_failed` → sets user.wallet_status = failed (and can trigger retries/alerts).
- Compensation: if a user must be rolled back after a wallet exists, User Service emits `user.creation_compensate`; Wallet Service deletes the wallet and emits `wallet.deleted`.