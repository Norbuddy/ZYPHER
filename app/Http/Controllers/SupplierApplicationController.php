<?php

namespace App\Http\Controllers;

use App\Models\SupplierApplication;
use Illuminate\Http\Request;

class SupplierApplicationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'business_name' => 'required|max:255',
            'business_email' => 'required|email',
            'contact_number' => 'required|max:50',
            'business_address' => 'required',
            'reason' => 'required',
            'permit' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $filename = time() . '_' . $request->file('permit')->getClientOriginalName();

        $request->file('permit')->move(
            public_path('supplier-permits'),
            $filename
        );

        $permitPath = 'supplier-permits/' . $filename;

        SupplierApplication::create([
            'user_id' => $request->user()->id,
            'business_name' => $request->business_name,
            'business_email' => $request->business_email,
            'contact_number' => $request->contact_number,
            'business_address' => $request->business_address,
            'reason' => $request->reason,
            'permit_path' => $permitPath,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Application submitted successfully.');
    }
}