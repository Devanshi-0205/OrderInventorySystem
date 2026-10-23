<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization handled by CheckRole middleware
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:191'],
            'sku'            => ['required', 'string', 'max:100', 'unique:products,sku'],
            'description'    => ['nullable', 'string'],
            'price'          => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
