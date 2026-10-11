<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SupplierApplicationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SupplierApplication/Index',['applications' => SupplierApplication::with('user')->latest()->get(),]);
    }

    public function approve(SupplierApplication $supplierApplication)
{

    $user = $supplierApplication->user;

if (! $user->currentTeam) {
    return back()->withErrors([
        'supplier' => 'This applicant must create a shop before becoming a supplier.',
    ]);
}
    DB::transaction(function () use ($supplierApplication) {

        $user = $supplierApplication->user;

        // Mark the user as supplier
        $user->update([
            'is_supplier' => true,
        ]);

        // Create Supplier record only if one does not exist yet
        if (!Supplier::where('team_id', $user->currentTeam?->id)->exists()) {

            Supplier::create([
                'team_id' => $user->currentTeam?->id,
                'name' => $supplierApplication->business_name,
                'contact_name' => $user->name,
                'email' => $supplierApplication->business_email,
                'phone' => $supplierApplication->contact_number,
                'address' => $supplierApplication->business_address,
                'notes' => $supplierApplication->reason,
            ]);
        }

        $supplierApplication->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

    });

    return back()->with('success', 'Supplier application approved successfully.');
}

    public function reject(Request $request, SupplierApplication $supplierApplication)
{
    $request->validate([
        'admin_note' => 'nullable|string|max:1000',
    ]);

    $supplierApplication->update([
        'status' => 'rejected',
        'admin_note' => $request->admin_note,
        'reviewed_at' => now(),
        'reviewed_by' => auth()->id(),
    ]);

    return back()->with('success', 'Supplier application rejected.');
}
}