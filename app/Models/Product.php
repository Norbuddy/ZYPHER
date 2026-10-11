<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id', 'branch_id', 'category_id', 'supplier_id', 'name', 'slug', 'description', 'status',
        'min_price', 'max_price', 'avg_rating', 'rating_count', 'sold_count', 'stock',
        'pos_available',
    ];

    protected $casts = [
        'status' => ProductStatus::class,
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'avg_rating' => 'decimal:2',
        'pos_available' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ProductStatus::Active);
    }

    /**
     * Recalculate min_price/max_price from active variants. Call after variant changes.
     */
    public function refreshPriceRange(): void
    {
        $prices = $this->variants()->where('is_active', true)->pluck('price');

        $this->update([
            'min_price' => $prices->min() ?? 0,
            'max_price' => $prices->max() ?? 0,
        ]);
    }

    public function refreshStock(): void
    {
        $total = (int) $this->variants()->sum('stock');
        $this->update(['stock' => $total]);
    }

    /**
     * Recalculate avg_rating/rating_count from reviews. Call after a review is added/removed.
     */
    public function refreshRating(): void
    {
        $this->update([
            'avg_rating' => $this->reviews()->avg('rating') ?? 0,
            'rating_count' => $this->reviews()->count(),
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === ProductStatus::Active;
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => strip_tags((string) $this->description),
            'team_id' => $this->team_id,
            'shop_name' => $this->team?->name,
            'category_id' => $this->category_id,
            'category_path' => $this->category ? $this->category->ancestorPath() : [],
            'min_price' => (float) $this->min_price,
            'max_price' => (float) $this->max_price,
            'avg_rating' => (float) $this->avg_rating,
            'rating_count' => $this->rating_count,
            'sold_count' => $this->sold_count,
            'created_at' => $this->created_at?->timestamp,
        ];
    }
}
