<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\SellerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    /**
     * Safely view/stream a seller permit or product legitimation document.
     * Restricted to administrators.
     */
    public function viewSellerDocument(Request $request, SellerApplication $sellerApplication, string $type): Response
    {
        abort_unless(in_array($type, ['permit', 'legitimation'], true), 404);

        $relPath = $type === 'permit'
            ? $sellerApplication->permit_path
            : $sellerApplication->product_legitimation_path;

        abort_unless($relPath, 404, 'Document record not found.');

        // 1. Check local private disk (storage/app/private/)
        if (Storage::disk('local')->exists($relPath)) {
            $disk = Storage::disk('local');
            return response()->file($disk->path($relPath), [
                'Content-Type' => $disk->mimeType($relPath) ?? 'application/octet-stream',
                'Content-Disposition' => 'inline',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        // 2. Fallback to legacy path in public_path if older application
        $legacyFullPath = public_path($relPath);
        if (file_exists($legacyFullPath) && is_file($legacyFullPath)) {
            $mime = mime_content_type($legacyFullPath) ?: 'application/octet-stream';
            return response()->file($legacyFullPath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        abort(404, 'Document file not found.');
    }

    /**
     * Safely view/stream a branch BIR registration document.
     * Restricted to administrators.
     */
    public function viewBranchBir(Request $request, Branch $branch): Response
    {
        $relPath = $branch->bir_document_path;
        abort_unless($relPath, 404, 'BIR document record not found.');

        // 1. Check local private disk (storage/app/private/)
        if (Storage::disk('local')->exists($relPath)) {
            $disk = Storage::disk('local');
            return response()->file($disk->path($relPath), [
                'Content-Type' => $disk->mimeType($relPath) ?? 'application/octet-stream',
                'Content-Disposition' => 'inline',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        // 2. Check public storage disk for legacy uploaded files
        if (Storage::disk('public')->exists($relPath)) {
            $disk = Storage::disk('public');
            return response()->file($disk->path($relPath), [
                'Content-Type' => $disk->mimeType($relPath) ?? 'application/octet-stream',
                'Content-Disposition' => 'inline',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        abort(404, 'BIR document file not found.');
    }
}
