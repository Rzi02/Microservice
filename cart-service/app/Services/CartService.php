<?php

namespace App\Services;

use App\Exceptions\CartItemNotFoundException;
use App\Exceptions\ProductNotFoundException;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        private ProductService $productService
    ) {}

    // Mengambil keranjang berdasarkan user
    public function getCart(int $userId): array
    {
        $items = CartItem::forUser($userId)->get();

        return [
            'user_id' => $userId,
            'items' => $items,
            'total_quantity' => $items->sum('quantity'),
        ];
    }

    // Menambahkan satu produk
    public function addItem(
        int $userId,
        int $productId,
        int $quantity
    ): CartItem {
        if (!$this->productService->getProduct($productId)) {
            throw new ProductNotFoundException($productId);
        }

        $item = CartItem::firstOrNew([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);

        $item->quantity = ($item->exists ? $item->quantity : 0)
            + $quantity;

        $item->save();

        return $item;
    }

    // Menambahkan beberapa produk sekaligus
    public function addItems(int $userId, array $items): array
    {
        return DB::transaction(function () use ($userId, $items) {
            $results = [];

            foreach ($items as $item) {
                $results[] = $this->addItem(
                    $userId,
                    (int) $item['product_id'],
                    (int) ($item['quantity'] ?? 1)
                );
            }

            return $results;
        });
    }

    // Memperbarui jumlah produk
    public function updateQuantity(
        int $id,
        int $quantity
    ): CartItem {
        $item = CartItem::find($id);

        if (!$item) {
            throw new CartItemNotFoundException($id);
        }

        $item->update([
            'quantity' => $quantity,
        ]);

        return $item;
    }

    // Menghapus satu produk
    public function removeItem(int $id): void
    {
        if (CartItem::destroy($id) === 0) {
            throw new CartItemNotFoundException($id);
        }
    }

    // Mengosongkan keranjang
    public function clearCart(int $userId): int
    {
        return CartItem::where('user_id', $userId)->delete();
    }
}