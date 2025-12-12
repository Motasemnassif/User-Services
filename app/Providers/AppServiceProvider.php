<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Src\Domain\Repositories\UserRepositoryInterface;
use Src\Domain\Services\EventPublisherInterface;
use Src\Domain\Services\PaymentGatewayInterface;
use Src\Infrastructure\Adapters\RabbitMQEventPublisher;
use Src\Infrastructure\Gateways\PaymentGateway;
use Src\Infrastructure\Repositories\EloquentUserRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );

        // Service bindings
        $this->app->bind(
            EventPublisherInterface::class,
            RabbitMQEventPublisher::class
        );

        $this->app->bind(
            PaymentGatewayInterface::class,
            PaymentGateway::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure Passport token expiration
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
    }
}
