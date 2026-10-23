<?php

namespace App\Clients;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CartServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.cart.url'),
            '/'
        );
    }

    public function getCart(int $userId): array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get("{$this->baseUrl}/carts/{$userId}");

            $response->throw();

            return $response->json();
        } catch (\Throwable $exception) {
            report($exception);

            throw new RuntimeException(
                'Cart Service tidak dapat mengambil keranjang.',
                0,
                $exception
            );
        }
    }

    public function clearCart(int $userId): void
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->delete("{$this->baseUrl}/carts/{$userId}");

            $response->throw();
        } catch (\Throwable $exception) {
            report($exception);

            throw new RuntimeException(
                'Cart Service gagal mengosongkan keranjang.',
                0,
                $exception
            );
        }
    }
}