<?php

namespace App\Clients;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ProductServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.product.url'),
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
                    "Produk dengan ID {$productId} tidak ditemukan."
                );
            }

            $response->throw();

            return $response->json();
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            throw new RuntimeException(
                'Product Service tidak dapat dihubungi.',
                0,
                $exception
            );
        }
    }
}