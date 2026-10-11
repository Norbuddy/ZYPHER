<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderShopStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership checked in controller
    }

    public function rules(): array
{
    return [
        'courier_name' => [
            'required',
            'string',
            'max:100',
            'in:Lalamove',
        ],

        'tracking_number' => [
            'nullable',
            'string',
            'max:100',
        ],

        'shipping_method' => [
            'nullable',
            'string',
            'max:100',
        ],

        'store_address' => [
            'required',
            'string',
            'max:1000',
        ],

        'store_latitude' => [
            'required',
            'numeric',
            'between:-90,90',
        ],

        'store_longitude' => [
            'required',
            'numeric',
            'between:-180,180',
        ],

        'delivery_distance_km' => [
            'required',
            'numeric',
            'min:0',
        ],

        'estimated_delivery_minutes' => [
            'required',
            'integer',
            'min:1',
        ],
    ];
}
}
