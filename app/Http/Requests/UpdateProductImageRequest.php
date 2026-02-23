<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['sometimes', 'integer', 'exists:products,id'],
            'image' => ['sometimes', 'file', 'image', 'max:5120'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
