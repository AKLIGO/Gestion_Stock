<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $productId = is_object($product) ? $product->getKey() : $product;

        return [
            'category_name' => ['sometimes', 'string', 'max:255', Rule::exists('categories', 'name')],
            'name' => ['sometimes', 'string', 'max:255'],
            'code_qr' => ['sometimes', 'string', 'max:255', 'unique:products,code_qr,' . $productId],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'images' => ['sometimes', 'array'],
            'images.*.file' => ['required_with:images', 'file', 'image', 'max:5120'],
            'images.*.is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
