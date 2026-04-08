<?php

namespace App\Models;

use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_variant_id',
        'on_hand',
        'reserved',
        'reorder_point',
        'reorder_quantity',
        'safety_stock',
        'last_restocked_at',
        'last_counted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_restocked_at' => 'datetime',
            'last_counted_at' => 'datetime',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function availableToSell(): int
    {
        return $this->on_hand - $this->reserved;
    }

    public function needsReorder(): bool
    {
        return $this->on_hand <= $this->reorder_point;
    }

    public function stockStatus(): string
    {
        return match (true) {
            $this->on_hand <= $this->safety_stock => 'critical',
            $this->needsReorder() => 'low',
            default => 'healthy',
        };
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('on_hand', '<=', 'reorder_point');
    }
}