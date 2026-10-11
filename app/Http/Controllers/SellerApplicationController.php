<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SellerApplication;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SellerApplicationController extends Controller
{
    public function create(Request $request): Response
{
    $existingApplication = null;

    if ($request->user()) {
        $existingApplication = SellerApplication::where('user_id', $request->user()->id)
            ->latest()
            ->first(['id', 'status', 'admin_note', 'created_at']);
    }

    return Inertia::render('SellerApplication/Create', [
        'categories' => Category::where('is_active', true)
            ->get(['id', 'name']),
        'existingApplication' => $existingApplication,
        'isGuest' => !$request->user(),
    ]);
}

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name' => 'required|max:255',
            'business_email' => 'required|email',
            'contact_number' => 'required|max:50',
            'business_location' => 'required|string',
            'permit' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'product_legitimation' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'reason' => 'nullable|string',
        ]);

        $permitFilename = time() . '_' . $request->file('permit')->getClientOriginalName();
        $request->file('permit')->move(public_path('seller-permits'), $permitFilename);

        $legitFilename = time() . '_' . $request->file('product_legitimation')->getClientOriginalName();
        $request->file('product_legitimation')->move(public_path('seller-permits'), $legitFilename);

        SellerApplication::create([
            'user_id' => $request->user()->id,
            'business_name' => $data['business_name'],
            'business_email' => $data['business_email'],
            'contact_number' => $data['contact_number'],
            'business_location' => $data['business_location'],
            'permit_path' => 'seller-permits/' . $permitFilename,
            'product_legitimation_path' => 'seller-permits/' . $legitFilename,
            'requested_category_ids' => $data['category_ids'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Seller application submitted successfully.');
    }
}