<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => ['required', 'string', 'max:255', Rule::exists('categories', 'name')],
            'name' => ['required', 'string', 'max:255'],
            'code_qr' => ['required', 'string', 'max:255', 'unique:products,code_qr'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'images' => ['sometimes', 'array'],
            'images.*.file' => ['required_with:images', 'file', 'image', 'max:5120'],
            'images.*.is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
