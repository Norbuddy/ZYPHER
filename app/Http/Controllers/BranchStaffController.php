<?php

namespace App\Http\Controllers;

use App\Mail\BranchAccountCreatedMail;
use App\Models\PosSale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BranchStaffController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isBranchManager() && $request->user()->branch_id, 403);

        $staff = User::query()
            ->where('branch_id', $request->user()->branch_id)
            ->whereIn('role', ['cashier', 'branch_staff'])
            ->latest()
            ->get(['id', 'name', 'email', 'contact_number', 'role', 'is_suspended']);

        $sales = PosSale::query()
            ->where('branch_id', $request->user()->branch_id)
            ->whereIn('user_id', $staff->pluck('id'))
            ->with('items')
            ->latest()
            ->get()
            ->map(fn (PosSale $sale) => [
                'id' => $sale->id,
                'user_id' => $sale->user_id,
                'sale_number' => $sale->sale_number,
                'total_amount' => (float) $sale->total_amount,
                'payment_method' => $sale->payment_method,
                'created_at' => $sale->created_at?->format('M d, Y h:i A'),
                'items' => $sale->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'variant_label' => $item->variant_label,
                    'quantity' => $item->quantity,
                ])->values(),
            ])->values();

        $salesByStaff = $sales->groupBy('user_id');

        return Inertia::render('Seller/Branch/Staff', [
            'branch' => $request->user()->branch?->only('id', 'name'),
            'staff' => $staff->map(function (User $member) use ($salesByStaff) {
                $memberSales = $salesByStaff->get($member->id, collect());

                return [
                    ...$member->toArray(),
                    'sales_count' => $memberSales->count(),
                    'sales_total' => (float) $memberSales->sum('total_amount'),
                ];
            }),
            'sales' => $sales,
        ]);
    }

    public function store(Request $request)
    {
        $manager = $request->user();
        abort_unless($manager->isBranchManager() && $manager->branch_id, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191'],
            'contact_number' => ['required', 'string', 'max:30'],
        ]);

        $temporaryPassword = 'Branch@' . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $notificationEmail = $data['email'];
        $loginEmail = $this->uniqueLoginEmail($notificationEmail);
        $staff = User::create([
            ...$data,
            'email' => $loginEmail,
            'password' => Hash::make($temporaryPassword),
            'role' => 'cashier',
            'branch_role' => 'cashier',
            'branch_id' => $manager->branch_id,
        ]);

        Mail::to($notificationEmail)->queue(new BranchAccountCreatedMail($staff, $temporaryPassword, $manager->branch->name));

        return back()->with('success', "Cashier {$staff->name} added. Login: {$staff->email}. Credentials sent to {$notificationEmail}.");
    }

    public function updateSuspension(Request $request, User $staff): RedirectResponse
    {
        $manager = $request->user();
        abort_unless($manager->isBranchManager() && $manager->branch_id, 403);
        abort_unless($this->belongsToManagedBranch($staff, $manager->branch_id), 404);

        $data = $request->validate([
            'is_suspended' => ['required', 'boolean'],
        ]);

        $staff->update(['is_suspended' => $data['is_suspended']]);

        return back()->with('success', $data['is_suspended']
            ? "{$staff->name}'s account has been suspended."
            : "{$staff->name}'s account has been reactivated.");
    }

    public function destroy(Request $request, User $staff): RedirectResponse
    {
        $manager = $request->user();
        abort_unless($manager->isBranchManager() && $manager->branch_id, 403);
        abort_unless($this->belongsToManagedBranch($staff, $manager->branch_id), 404);

        $staff->delete();

        return back()->with('success', "{$staff->name}'s account has been deleted.");
    }

    private function belongsToManagedBranch(User $staff, int $branchId): bool
    {
        return (int) $staff->branch_id === $branchId
            && in_array($staff->role, ['cashier', 'branch_staff'], true);
    }

    private function uniqueLoginEmail(string $email): string
    {
        if (! User::where('email', $email)->exists()) {
            return $email;
        }

        [$localPart, $domain] = explode('@', $email, 2);

        do {
            $loginEmail = $localPart . '+branch-' . Str::lower(Str::random(6)) . '@' . $domain;
        } while (User::where('email', $loginEmail)->exists());

        return $loginEmail;
    }
}