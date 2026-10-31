<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    // Membuat pesanan
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

            if (!in_array($status, [409, 422, 503], true)) {
                $status = 500;

                Log::error('Checkout gagal.', [
                    'error' => $exception->getMessage(),
                ]);
            }

            return response()->json([
                'message' => $exception->getMessage(),
                ...(
                    config('app.debug')
                        ? ['debug' => $exception->getMessage()]
                        : []
                ),
            ], $status);
        }
    }

    // Melihat satu pesanan
    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->show($id);

        if (!$order) {
            return response()->json([
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail pesanan berhasil diambil.',
            'data' => $order,
        ], 200);
    }

    // Melihat pesanan berdasarkan user
    public function userOrders(string $userId): JsonResponse
    {
        $validator = Validator::make(
            ['user_id' => $userId],
            [
                'user_id' => ['required', 'integer', 'min:1'],
            ],
            [
                'user_id.required' => 'ID pengguna wajib diisi.',
                'user_id.integer' => 'ID pengguna harus berupa angka.',
                'user_id.min' => 'ID pengguna harus lebih besar dari 0.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $orders = $this->orderService->userOrders((int) $userId);

        return response()->json([
            'message' => 'Daftar pesanan berhasil diambil.',
            'data' => $orders,
        ], 200);
    }

    // Membayar pesanan
    public function pay(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->pay($id);

            if (!$order) {
                return response()->json([
                    'message' => 'Pesanan tidak ditemukan.',
                ], 404);
            }

            return response()->json([
                'message' => 'Pembayaran berhasil.',
                'data' => $order,
            ], 200);

        } catch (Throwable $exception) {
            $status = (int) $exception->getCode();

            if (!in_array($status, [409, 422, 503], true)) {
                $status = 500;

                Log::error('Pembayaran gagal.', [
                    'error' => $exception->getMessage(),
                ]);
            }

            return response()->json([
                'message' => $exception->getMessage(),
                ...(
                    config('app.debug')
                        ? ['debug' => $exception->getMessage()]
                        : []
                ),
            ], $status);
        }
    }
}
