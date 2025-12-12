<?php

declare(strict_types=1);

namespace Src\Domain\Services;

interface PaymentGatewayInterface
{
    /**
     * @param array<string, mixed> $paymentData
     * @return array<string, mixed>
     */
    public function processPayment(array $paymentData): array;

    /**
     * @param string $transactionId
     * @return array<string, mixed>
     */
    public function getPaymentStatus(string $transactionId): array;
}
