<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentTeam?->isApproved() ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'publish' => ['nullable', 'boolean'],

            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'max:5120'], // 5MB per image

            'options' => ['nullable', 'array'],
            'options.*.name' => ['required_with:options', 'string', 'max:100'],
            'options.*.values' => ['required_with:options', 'array', 'min:1'],
            'options.*.values.*' => ['required', 'string', 'max:100'],

            'variants' => ['required', 'array', 'min:1'],
            'variants.*.sku' => ['nullable', 'string', 'unique:product_variants,sku'],
            'variants.*.label' => ['nullable', 'string'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.option_value_labels' => ['nullable', 'array'],
            'seller_inventory_id' => ['nullable', 'integer', 'exists:seller_inventories,id'],
        ];
    }
}
