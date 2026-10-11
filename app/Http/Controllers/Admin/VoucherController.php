<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    public function index(): Response
    {
        $vouchers = Voucher::where('owner_type', 'platform')->latest()->get();

        return Inertia::render('Admin/Vouchers/Index', ['vouchers' => $vouchers]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:40', 'alpha_dash', 'unique:vouchers,code'],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed_amount'])],
            'discount_value' => [
                'required',
                'numeric',
                'min:0.01',
                Rule::when($request->input('discount_type') === 'percentage', ['max:100']),
            ],
            'min_spend' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit_total' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        if (empty($data['code'])) {
            $data['code'] = strtoupper(Str::random(10));
        } else {
            $data['code'] = strtoupper(preg_replace('/[^A-Za-z0-9\-_]/', '', $data['code']));
        }

        Voucher::create([
            ...$data,
            'owner_type' => 'platform',
            'team_id' => null,
            'status' => 'active',
        ]);

        return back()->with('success', 'Platform voucher created.');
    }
}
