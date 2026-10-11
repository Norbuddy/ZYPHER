<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'path', 'sort_order', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    protected $appends = ['url'];

    /**
     * Images are saved with ProductController to public/images/products/{file}
     * (path in DB = "images/products/filename.jpg"), NOT under storage/app/public.
     * Fall back to Storage::url only if the path looks like a storage path.
     */
    public function getUrlAttribute(): string
    {
        $path = ltrim((string) $this->path, '/');

        if ($path === '') {
            return '';
        }

        // Already a full URL
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Primary case: files live in public/images/products/...
        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        // Legacy / alternate: storage disk
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Last resort: treat as public-relative path
        return asset($path);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
