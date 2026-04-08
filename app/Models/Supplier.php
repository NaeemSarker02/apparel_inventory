<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ON_HOLD = 'on-hold';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_code',
        'name',
        'contact_person',
        'email',
        'phone',
        'lead_time_days',
        'payment_terms_days',
        'address',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ON_HOLD => 'On hold',
            self::STATUS_INACTIVE => 'Inactive',
        ];
    }

    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function activeVariantCount(): int
    {
        return $this->productVariants()
            ->where('is_active', true)
            ->count();
    }

    public function purchaseOrderCount(): int
    {
        return $this->purchaseOrders()->count();
    }

    public function receivedPurchaseSpend(): float
    {
        return (float) $this->purchaseOrders()
            ->where('status', PurchaseOrder::STATUS_RECEIVED)
            ->sum('total_amount');
    }

    public function latestPurchaseOrderDate(): ?Carbon
    {
        $orderedAt = $this->purchaseOrders()->max('ordered_at');

        return $orderedAt ? Carbon::parse($orderedAt) : null;
    }
}
