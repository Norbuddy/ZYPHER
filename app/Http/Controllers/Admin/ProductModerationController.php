<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductModerationController extends Controller
{
    public function index(): Response
    {
        $products = Product::with('team:id,name')->latest()->paginate(20);

        return Inertia::render('Admin/Products/Index', ['products' => $products]);
    }

    public function flag(Product $product): RedirectResponse
    {
        $product->update(['status' => 'flagged']);

        return back()->with('success', 'Product flagged and hidden from search.');
    }

    public function unflag(Product $product): RedirectResponse
    {
        $product->update(['status' => 'active']);

        return back()->with('success', 'Product restored.');
    }
}
