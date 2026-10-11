<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    public function index(Request $request): Response
    {
        $vouchers = $request->user()->currentTeam->vouchers()->latest()->get();

        return Inertia::render('Seller/Vouchers/Index', ['vouchers' => $vouchers]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'unique:vouchers,code'],
            'discount_type' => ['required', 'in:percentage,fixed_amount'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'min_spend' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit_total' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        $request->user()->currentTeam->vouchers()->create([
            ...$data,
            'owner_type' => 'shop',
            'status' => 'active',
        ]);

        return back()->with('success', 'Voucher created.');
    }
}
