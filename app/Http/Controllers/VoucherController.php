<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    /** Validate & preview a voucher's discount before it's committed at checkout. */
    public function preview(Request $request, VoucherService $voucherService): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $voucher = Voucher::where('code', $data['code'])->first();

        if (!$voucher) {
            return response()->json(['error' => 'Voucher not found.'], 404);
        }

        $error = $voucherService->validate($voucher, $request->user(), $data['subtotal']);

        if ($error) {
            return response()->json(['error' => $error], 422);
        }

        return response()->json([
            'voucher_id' => $voucher->id,
            'discount' => $voucher->calculateDiscount($data['subtotal']),
        ]);
    }
}
