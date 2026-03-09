<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'            => ['required', 'integer', 'min:1'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'              => 'At least one item is required.',
            'items.min'                   => 'At least one item is required.',
            'items.*.product_id.required' => 'Each item must have a product_id.',
            'items.*.quantity.min'        => 'Quantity must be at least 1.',
        ];
    }
}