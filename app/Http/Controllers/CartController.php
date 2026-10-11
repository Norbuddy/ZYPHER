<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(Request $request): Response
    {
        $cart = $request->user()->cart()->firstOrCreate([]);

        return Inertia::render('Cart/Index', [
            'itemsByShop' => $cart->itemsGroupedByShop(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variant = ProductVariant::findOrFail($data['product_variant_id']);

        if (!$variant->hasStockFor($data['quantity'])) {
            return back()->withErrors(['quantity' => 'Not enough stock available.']);
        }

        $cart = $request->user()->cart()->firstOrCreate([]);

        $item = $cart->items()->firstOrNew(['product_variant_id' => $variant->id]);
        $item->quantity = ($item->exists ? $item->quantity : 0) + $data['quantity'];
        $item->save();

        return back()->with('success', 'Added to cart.');
    }

    public function update(Request $request, int $cartItemId): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);

        $item = $request->user()->cart->items()->findOrFail($cartItemId);
        $item->update(['quantity' => $data['quantity']]);

        return back();
    }

    public function destroy(Request $request, int $cartItemId): RedirectResponse
    {
        $request->user()->cart->items()->findOrFail($cartItemId)->delete();

        return back();
    }
}
