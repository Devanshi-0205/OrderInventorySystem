<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization handled by CheckRole middleware
    }

    public function rules(): array
    {
        // Exclude the current product's own SKU from the unique check
        $productId = $this->route('product')->id;

        return [
            'name'           => ['required', 'string', 'max:191'],
            'sku'            => ['required', 'string', 'max:100', "unique:products,sku,{$productId}"],
            'description'    => ['nullable', 'string'],
            'price'          => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
