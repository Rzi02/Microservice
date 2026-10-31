<?php

namespace App\Services;

use App\Clients\CartServiceClient;
use App\Clients\ProductServiceClient;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OrderService
{
    public function __construct(
        private CartServiceClient $cartClient,
        private ProductServiceClient $productClient
    ) {}

    // Membuat pesanan dari keranjang
    public function checkout(int $userId): Order
    {
        $cart = $this->cartClient->getCart($userId);
        $items = $cart['data']['items'] ?? [];

        if (count($items) === 0) {
            throw new RuntimeException('Keranjang masih kosong.', 409);
        }

        $orderItems = [];
        $total = 0;

        foreach ($items as $item) {
            $productId = (int) (
                is_array($item) ? $item['product_id'] : $item->product_id
            );

            $quantity = (int) (
                is_array($item) ? $item['quantity'] : $item->quantity
            );

            $product = $this->productClient->getProduct($productId);

            $stock = (int) ($product['stock'] ?? 0);
            $name = (string) ($product['name'] ?? '');
            $price = (float) ($product['price'] ?? 0);

            if ($quantity < 1 || $stock < $quantity) {
                throw new RuntimeException(
                    "Stok produk {$productId} tidak mencukupi.", 409
                );
            }

            if ($name === '' || $price < 0) {
                throw new RuntimeException(
                    "Data produk {$productId} tidak valid.", 503
                );
            }

            $subtotal = round($price * $quantity, 2);
            $total += $subtotal;

            $orderItems[] = [
                'product_id' => $productId,
                'product_name' => $name,
                'quantity' => $quantity,
                'unit_price' => $price,
                'subtotal' => $subtotal,
            ];
        }

        $order = DB::transaction(function () use (
            $userId, $orderItems, $total
        ) {
            $order = Order::create([
                'user_id' => $userId,
                'total_amount' => $total,
                'status' => 'PENDING',
            ]);

            $order->items()->createMany($orderItems);

            return $order->load('items');
        });

        try {
            $this->cartClient->clearCart($userId);
        } catch (\Throwable $exception) {
            Log::error('Pesanan tersimpan, tetapi keranjang gagal dikosongkan.', [
                'order_id' => $order->id,
                'user_id' => $userId,
                'error' => $exception->getMessage(),
            ]);
        }

        return $order;
    }

    // Melihat satu pesanan
    public function show(int $id): ?Order
    {
        return Order::with('items')->find($id);
    }

    // Melihat semua pesanan milik pengguna
    public function userOrders(int $userId)
    {
        return Order::with('items')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    // Melakukan pembayaran
    public function pay(int $id): ?Order
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return null;
        }

        if ($order->status !== 'PENDING') {
            throw new RuntimeException(
                'Status pesanan bukan PENDING.', 409
            );
        }

        $order->update([
            'status' => 'PAID',
        ]);

        return $order->fresh('items');
    }
}
