<?php

namespace App\Clients;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ProductServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            (string) config('services.product.url'),
            '/'
        );
    }

    public function getProduct(int $productId): array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get("{$this->baseUrl}/products/{$productId}");

            if ($response->status() === 404) {
                throw new RuntimeException(
                    "Produk dengan ID {$productId} tidak ditemukan.",
                    404
                );
            }

            $response->throw();

            $body = $response->json();
            $product = $body['data'] ?? null;

            if (! is_array($product)) {
                throw new RuntimeException(
                    'Format respons Product Service tidak valid.'
                );
            }

            return $product;
        } catch (Throwable $exception) {
            if ($exception->getCode() === 404) {
                throw $exception;
            }

            report($exception);

            throw new RuntimeException(
                'Product Service tidak dapat dihubungi atau memberikan respons tidak valid.',
                0,
                $exception
            );
        }
    }
}