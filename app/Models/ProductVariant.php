<?php

namespace App\Models;

use App\Models\Inventory;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'supplier_id',
        'sku',
        'barcode',
        'size',
        'color_name',
        'color_code',
        'season',
        'unit_cost',
        'sale_price',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function seasonOptions(): array
    {
        return [
            'spring' => 'Spring',
            'summer' => 'Summer',
            'autumn' => 'Autumn',
            'winter' => 'Winter',
            'all-season' => 'All season',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (ProductVariant $variant): void {
            if (! $variant->inventory()->exists()) {
                $variant->inventory()->create();
            }
        });
    }

    public function getDisplayNameAttribute(): string
    {
        return sprintf(
            '%s / %s / %s / %s',
            $this->product?->name ?? 'Variant',
            $this->size,
            $this->color_name,
            ucfirst($this->season)
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function salesOrderItems(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
