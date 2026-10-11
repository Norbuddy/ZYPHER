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

        $base = basename($relPath);
        $candidates = array_unique([
            ltrim($relPath, '/\\'),
            'seller-permits/' . $base,
            'supplier-permits/' . $base,
            $base,
        ]);

        $disk = Storage::disk('local');
        foreach ($candidates as $candidate) {
            if ($disk->exists($candidate)) {
                return response()->file($disk->path($candidate), [
                    'Content-Type' => $disk->mimeType($candidate) ?? 'application/octet-stream',
                    'Content-Disposition' => 'inline',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        // Fallback to legacy path in public_path if older application
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

        $base = basename($relPath);
        $candidates = array_unique([
            ltrim($relPath, '/\\'),
            'bir_documents/' . $base,
            $base,
        ]);

        $disk = Storage::disk('local');
        foreach ($candidates as $candidate) {
            if ($disk->exists($candidate)) {
                return response()->file($disk->path($candidate), [
                    'Content-Type' => $disk->mimeType($candidate) ?? 'application/octet-stream',
                    'Content-Disposition' => 'inline',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        // Fallback to public storage disk for legacy uploaded files
        if (Storage::disk('public')->exists($relPath)) {
            $pubDisk = Storage::disk('public');
            return response()->file($pubDisk->path($relPath), [
                'Content-Type' => $pubDisk->mimeType($relPath) ?? 'application/octet-stream',
                'Content-Disposition' => 'inline',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        abort(404, 'BIR document file not found.');
    }
}
