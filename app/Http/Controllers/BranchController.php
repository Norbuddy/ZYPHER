<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Models\PosSale;
use App\Mail\BranchAccountCreatedMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::query()
            ->where('user_id', $request->user()->id)
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
                    'bir_document_path' => $branch->bir_document_path,
                    'status' => $branch->status,
                    'created_at' => $branch->created_at?->format('M d, Y'),
                ];
            });

        return Inertia::render('Seller/Branches/Index', [
            'branches' => $branches,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'phone' => ['required', 'string', 'max:30'],
            'region' => ['nullable', 'string', 'max:191'],
            'province' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:191'],
            'barangay' => ['nullable', 'string', 'max:191'],
            'address' => ['required', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'bir_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'email' => ['required', 'email', 'max:191'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'branch_role' => ['nullable', 'in:manager,cashier'],
        ]);

        $legacyPayload = array_key_exists('password', $data) && filled($data['password']);
        $defaultPassword = $legacyPayload
            ? $data['password']
            : 'Br#' . Str::random(10);
        $notificationEmail = $data['email'];
        $loginEmail = $legacyPayload ? $data['email'] : $this->uniqueLoginEmail($data['email']);

        [$branch, $user] = DB::transaction(function () use ($data, $request, $defaultPassword, $legacyPayload, $loginEmail) {
            $path = $request->file('bir_document')->store('bir_documents', 'local');

            $branch = Branch::create([
                'user_id' => $request->user()->id,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'region' => $data['region'] ?? null,
                'province' => $data['province'] ?? null,
                'city' => $data['city'] ?? null,
                'barangay' => $data['barangay'] ?? null,
                'address' => $data['address'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'bir_document_path' => $path,
                'status' => 'active',
            ]);

            $user = User::create([
                'name' => $branch->name . ' Manager',
                'email' => $loginEmail,
                'password' => Hash::make($defaultPassword),
                'role' => $legacyPayload ? 'branch_user' : 'branch_manager',
                'branch_role' => $legacyPayload ? ($data['branch_role'] ?? 'manager') : 'manager',
                'branch_id' => $branch->id,
            ]);

            return [$branch, $user];
        });

        if (! $legacyPayload) {
            Mail::to($notificationEmail)->queue(new BranchAccountCreatedMail($user, $defaultPassword, $branch->name));
        }

        return back()->with('success', "Branch {$branch->name} added. Login: {$user->email}. Credentials sent to {$notificationEmail}.");
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

    public function viewBirDocument(Request $request, Branch $branch): Response
    {
        $user = $request->user();

        abort_unless(
            $user->role === 'admin'
                || $user->id === $branch->user_id
                || $user->branch_id === $branch->id,
            403,
            'Unauthorized access to BIR document.'
        );

        $disk = null;
        if ($branch->bir_document_path && Storage::disk('local')->exists($branch->bir_document_path)) {
            $disk = Storage::disk('local');
        } elseif ($branch->bir_document_path && Storage::disk('public')->exists($branch->bir_document_path)) {
            $disk = Storage::disk('public');
        }

        abort_unless($disk !== null, 404, 'BIR document not found.');

        return response()->file($disk->path($branch->bir_document_path), [
            'Content-Type' => $disk->mimeType($branch->bir_document_path) ?? 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function sales(Request $request, Branch $branch): InertiaResponse|JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user->role === 'admin'
                || $user->id === $branch->user_id
                || $user->branch_id === $branch->id,
            403,
            'Unauthorized access to branch sales.'
        );

        $saleRecords = PosSale::query()
            ->where('branch_id', $branch->id)
            ->with('items')
            ->latest()
            ->get();

        $sales = $saleRecords
            ->map(fn (PosSale $sale) => [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'items' => $sale->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'variant_label' => $item->variant_label,
                    'quantity' => $item->quantity,
                ])->values(),
                'total_amount' => (float) $sale->total_amount,
                'payment_method' => $sale->payment_method,
                'created_at' => $sale->created_at?->format('M d, Y h:i A'),
            ]);

        $salesChart = $saleRecords
            ->groupBy(fn (PosSale $sale) => $sale->created_at?->format('M d'))
            ->map(fn ($daySales, $date) => [
                'date' => $date,
                'total' => (float) $daySales->sum('total_amount'),
                'sales_count' => $daySales->count(),
            ])
            ->sortKeys()
            ->values();

        $payload = [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
            ],
            'sales' => $sales,
            'sales_chart' => $salesChart,
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return Inertia::render('Seller/Branches/Sales', $payload);
    }
}
