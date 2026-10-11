<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Category;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow suppliers who belong to a team
        return (bool) $this->user()?->is_supplier;
    }

    public function rules(): array
    {
        // Allowed category names enforced here to prevent suppliers creating new categories
        $allowed = [
            'Electronics', 'Toys', 'Shoes', 'Slippers',
            'Pants', 'T-Shirts', 'Long Sleeves', 'Jacket',
        ];

        $allowedCategoryIds = Category::whereIn('name', $allowed)->pluck('id')->toArray();

        // If categories haven't been created yet, fall back to exists rule
        $inRule = count($allowedCategoryIds) ? ('in:' . implode(',', $allowedCategoryIds)) : 'exists:categories,id';

        return [
            'category_id' => ['required', $inRule],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],

            // For size-enabled categories, client should send `size_variants` instead of `stock`.
            'size_variants' => ['nullable', 'array'],
            'size_variants.*.size' => ['required_with:size_variants', 'string'],
            'size_variants.*.stock' => ['required_with:size_variants', 'integer', 'min:0'],

            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'max:5120'],
        ];
    }
}
