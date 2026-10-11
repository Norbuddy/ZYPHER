<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BranchApplicationController extends Controller
{
    public function index()
    {
        $applications = Branch::query()
            ->with('user')
            ->latest()
            ->get()
            ->map(function (Branch $branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'phone' => $branch->phone,
                    'address' => $branch->address,
                    'latitude' => $branch->latitude,
                    'longitude' => $branch->longitude,
                    'status' => in_array(strtolower((string) $branch->status), ['active', 'approved'], true)
                        ? 'active'
                        : 'suspended',
                    'bir_document_path' => $branch->bir_document_path,
                    'user' => $branch->user ? [
                        'id' => $branch->user->id,
                        'name' => $branch->user->name,
                        'email' => $branch->user->email,
                    ] : null,
                    'created_at' => $branch->created_at?->toISOString(),
                ];
            });

        return Inertia::render('Admin/BranchApplication/Index', [
            'applications' => $applications,
        ]);
    }

    public function approve(Branch $branch)
    {
        $branch->update([
            'status' => 'active',
        ]);

        return back()->with('success', 'Branch activated.');
    }

    public function suspend(Branch $branch)
    {
        $branch->update(['status' => 'suspended']);

        return back()->with('success', 'Branch suspended.');
    }

    public function activate(Branch $branch)
    {
        $branch->update(['status' => 'active']);

        return back()->with('success', 'Branch activated.');
    }

    public function destroy(Branch $branch)
    {
        $branch->staff()->update(['branch_id' => null]);
        $branch->delete();

        return back()->with('success', 'Branch deleted.');
    }

    public function reject(Request $request, Branch $branch)
    {
        $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $branch->update([
            'status' => 'suspended',
        ]);

        return back()->with('success', 'Branch suspended.');
    }
}
