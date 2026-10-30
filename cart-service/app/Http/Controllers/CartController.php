<?php

namespace App\Http\Controllers;

use App\Exceptions\CartItemNotFoundException;
use App\Exceptions\ProductNotFoundException;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    // GET /carts/{userId}
    public function show(int $userId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Berhasil mengambil keranjang',
                'data' => $this->cartService->getCart($userId),
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($e, 'mengambil keranjang');
        }
    }

    // POST /carts
    public function store(AddCartItemRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $userId = (int) $data['user_id'];

            Log::info('Data POST cart', [
                'data' => $data,
                'product_service_url' => config('services.product.url'),
            ]);

            // Jika mengirim beberapa produk
            if (isset($data['items'])) {
                $items = $this->cartService->addItems(
                    $userId,
                    $data['items']
                );

                return response()->json([
                    'message' => 'Beberapa item berhasil ditambahkan ke keranjang.',
                    'data' => $items,
                ], 201);
            }

            // Jika hanya mengirim satu produk
            $item = $this->cartService->addItem(
                $userId,
                (int) $data['product_id'],
                (int) ($data['quantity'] ?? 1)
            );

            return response()->json([
                'message' => 'Item berhasil ditambahkan ke keranjang',
                'data' => $item,
            ], $item->wasRecentlyCreated ? 201 : 200);

        } catch (ProductNotFoundException $e) {
            Log::warning('Produk tidak ditemukan saat POST cart', [
                'message' => $e->getMessage(),
                'product_service_url' => config('services.product.url'),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 404);

        } catch (Throwable $e) {
            return $this->errorResponse($e, 'menambahkan item');
        }
    }

    // PUT /carts/items/{id}
    public function update(
        UpdateCartItemRequest $request,
        int $id
    ): JsonResponse {
        try {
            $item = $this->cartService->updateQuantity(
                $id,
                $request->validated()['quantity']
            );

            return response()->json([
                'message' => 'Jumlah item berhasil diperbarui',
                'data' => $item,
            ]);
        } catch (CartItemNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            return $this->errorResponse($e, 'memperbarui item');
        }
    }

    // DELETE /carts/items/{id}
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->cartService->removeItem($id);

            return response()->json([
                'message' => 'Item berhasil dihapus dari keranjang',
            ]);
        } catch (CartItemNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            return $this->errorResponse($e, 'menghapus item');
        }
    }

    // DELETE /carts/{userId}
    public function clear(int $userId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Keranjang berhasil dikosongkan',
                'data' => [
                    'deleted_items' => $this->cartService->clearCart($userId),
                ],
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($e, 'mengosongkan keranjang');
        }
    }

    // Menangani error yang tidak terduga
    private function errorResponse(
        Throwable $e,
        string $action
    ): JsonResponse {
        Log::error("Gagal {$action}", [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        $body = [
            'message' => "Gagal {$action}",
        ];

        if (config('app.debug')) {
            $body['error'] = $e->getMessage();
            $body['location'] = $e->getFile() . ':' . $e->getLine();
        }

        return response()->json($body, 500);
    }
}
