<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'image_path', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function isLeaf(): bool
    {
        return $this->children()->doesntExist();
    }

    /**
     * Flattened array of this category's id plus every ancestor id.
     * Used for faceted search so filtering a parent category catches all descendants.
     */
    public function ancestorPath(): array
    {
        $path = [$this->id];
        $node = $this;

        while ($node->parent_id) {
            $node = $node->parent;
            if (!$node) break;
            $path[] = $node->id;
        }

        return $path;
    }
}
