<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    // Checkout pesanan
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->checkout(
                (int) $request->validated()['user_id']
            );

            return response()->json([
                'message' => 'Checkout berhasil.',
                'data' => $order,
            ], 201);

        } catch (Throwable $exception) {
            $status = (int) $exception->getCode();

            if (! in_array($status, [409, 422, 503], true)) {
                $status = 500;

                Log::error('Checkout gagal.', [
                    'error' => $exception->getMessage(),
                ]);
            }

            return response()->json([
                'message' => $exception->getMessage(),
                ...(config('app.debug')
                    ? ['debug' => $exception->getMessage()]
                    : []),
            ], $status);
        }
    }

    // Melihat detail pesanan
    public function show(int $id): JsonResponse
    {
        $order = Order::with('items')->find($id);

        if (! $order) {
            return response()->json([
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail pesanan berhasil diambil.',
            'data' => $order,
        ], 200);
    }

    // Melihat daftar pesanan berdasarkan user
    public function userOrders(
        Request $request,
        int $userId
    ): JsonResponse {
        $validated = $request->validate([
            'status' => [
                'sometimes',
                'string',
                Rule::in(['PENDING', 'PAID']),
            ],
        ]);

        $query = Order::with('items')
            ->where('user_id', $userId)
            ->latest();

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        return response()->json([
            'message' => 'Daftar pesanan berhasil diambil.',
            'data' => $query->get(),
        ], 200);
    }

    // Membayar pesanan
    public function pay(int $id): JsonResponse
    {
        $order = Order::with('items')->find($id);

        if (! $order) {
            return response()->json([
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        if ($order->status !== 'PENDING') {
            return response()->json([
                'message' => 'Pesanan tidak dapat dibayar karena status bukan PENDING.',
            ], 409);
        }

        $order->status = 'PAID';
        $order->save();

        $order->load('items');

        return response()->json([
            'message' => 'Pembayaran berhasil.',
            'data' => $order,
        ], 200);
    }
}