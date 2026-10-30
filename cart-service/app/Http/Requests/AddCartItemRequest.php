<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|min:1',

            // Untuk satu produk
            'product_id' => 'required_without:items|integer|min:1',
            'quantity' => 'sometimes|integer|min:1',

            // Untuk beberapa produk
            'items' => 'required_without:product_id|array|min:1',
            'items.*.product_id' => 'required|integer|min:1',
            'items.*.quantity' => 'sometimes|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Field :attribute wajib diisi.',
            'integer' => 'Field :attribute harus berupa angka bulat.',
            'min' => 'Field :attribute minimal bernilai :min.',
            'items.array' => 'Items harus berupa array.',
            'items.min' => 'Keranjang harus memiliki minimal satu item.',
        ];
    }
}
