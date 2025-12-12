<?php

declare(strict_types=1);

namespace Src\Infrastructure\Gateways;

use Illuminate\Support\Facades\Http;
use Src\Domain\Services\PaymentGatewayInterface;

final class PaymentGateway implements PaymentGatewayInterface
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.payment.base_url', 'https://api.payment-service.com');
        $this->apiKey = config('services.payment.api_key', '');
    }

    /**
     * @param array<string, mixed> $paymentData
     * @return array<string, mixed>
     */
    public function processPayment(array $paymentData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/payments", $paymentData);

            if ($response->failed()) {
                throw new \RuntimeException(
                    'Payment processing failed: ' . $response->body(),
                    $response->status()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            throw new \RuntimeException('Payment gateway error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string $transactionId
     * @return array<string, mixed>
     */
    public function getPaymentStatus(string $transactionId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/payments/{$transactionId}");

            if ($response->failed()) {
                throw new \RuntimeException(
                    'Failed to get payment status: ' . $response->body(),
                    $response->status()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            throw new \RuntimeException('Payment gateway error: ' . $e->getMessage(), 0, $e);
        }
    }
}
