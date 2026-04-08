<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InventorySystemSeeder extends Seeder
{
    public function run(): void
    {
        fake()->seed(40512);

        $users = $this->seedUsers();
        [$categories, $brands, $suppliers] = $this->seedReferenceData();
        $variants = $this->seedCatalog($categories, $brands, $suppliers);

        $inventoryService = app(InventoryService::class);

        $this->seedPurchaseOrders($variants, $users, $suppliers, $inventoryService);
        $this->seedSalesOrders($variants, $users, $inventoryService);
        $this->seedManualAdjustments($variants, $users, $inventoryService);
    }

    protected function seedUsers(): Collection
    {
        return collect([
            User::query()->updateOrCreate(
                ['email' => 'admin@test.com'],
                [
                    'name' => 'AIMS Administrator',
                    'role' => User::ROLE_ADMIN,
                    'is_active' => true,
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            ),
            User::query()->updateOrCreate(
                ['email' => 'manager@test.com'],
                [
                    'name' => 'Inventory Manager',
                    'role' => User::ROLE_MANAGER,
                    'is_active' => true,
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            ),
            User::query()->updateOrCreate(
                ['email' => 'viewer@test.com'],
                [
                    'name' => 'Read Only Analyst',
                    'role' => User::ROLE_VIEWER,
                    'is_active' => true,
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            ),
        ]);
    }

    /**
     * @return array{0: Collection<int, Category>, 1: Collection<int, Brand>, 2: Collection<int, Supplier>}
     */
    protected function seedReferenceData(): array
    {
        $categories = collect([
            ['name' => 'Tops', 'code' => 'TOPS'],
            ['name' => 'Bottoms', 'code' => 'BTMS'],
            ['name' => 'Dresses', 'code' => 'DRSS'],
            ['name' => 'Outerwear', 'code' => 'OUTR'],
            ['name' => 'Footwear', 'code' => 'FOOT'],
            ['name' => 'Accessories', 'code' => 'ACCS'],
        ])->map(fn (array $category) => Category::query()->updateOrCreate(
            ['code' => $category['code']],
            [
                'name' => $category['name'],
                'description' => $category['name'].' assortment and seasonal grouping.',
                'is_active' => true,
            ],
        ));

        $brands = collect([
            ['name' => 'Aster Thread', 'code' => 'ASTR', 'origin_country' => 'Australia'],
            ['name' => 'Urban Loom', 'code' => 'URBN', 'origin_country' => 'Bangladesh'],
            ['name' => 'Northline', 'code' => 'NRTL', 'origin_country' => 'China'],
            ['name' => 'Canvas Theory', 'code' => 'CNVS', 'origin_country' => 'India'],
            ['name' => 'Verdant Wear', 'code' => 'VRDT', 'origin_country' => 'Vietnam'],
            ['name' => 'Motion Atelier', 'code' => 'MTAT', 'origin_country' => 'Turkey'],
            ['name' => 'Metro Stitch', 'code' => 'MTRS', 'origin_country' => 'Indonesia'],
            ['name' => 'Harbor Mode', 'code' => 'HRBR', 'origin_country' => 'Portugal'],
        ])->map(fn (array $brand) => Brand::query()->updateOrCreate(
            ['code' => $brand['code']],
            [
                'name' => $brand['name'],
                'origin_country' => $brand['origin_country'],
                'description' => $brand['name'].' contemporary apparel line.',
                'is_active' => true,
            ],
        ));

        $suppliers = collect([
            ['supplier_code' => 'SUP-1001', 'name' => 'Pacific Apparel Sourcing'],
            ['supplier_code' => 'SUP-1002', 'name' => 'Meridian Textile Partners'],
            ['supplier_code' => 'SUP-1003', 'name' => 'Blueport Fashion Supply'],
            ['supplier_code' => 'SUP-1004', 'name' => 'Summit Stitch Logistics'],
            ['supplier_code' => 'SUP-1005', 'name' => 'Everline Wholesale'],
            ['supplier_code' => 'SUP-1006', 'name' => 'Contour Procurement House'],
        ])->map(function (array $supplier, int $index) {
            return Supplier::query()->updateOrCreate(
                ['supplier_code' => $supplier['supplier_code']],
                [
                    'name' => $supplier['name'],
                    'contact_person' => fake()->name(),
                    'email' => 'supply'.($index + 1).'@aims.test',
                    'phone' => '+61 3 9000 '.str_pad((string) ($index + 11), 4, '0', STR_PAD_LEFT),
                    'lead_time_days' => fake()->numberBetween(5, 18),
                    'payment_terms_days' => fake()->randomElement([14, 30, 45]),
                    'address' => fake()->streetAddress().', Melbourne VIC',
                    'status' => Supplier::STATUS_ACTIVE,
                    'notes' => 'Primary apparel supplier for seasonal replenishment.',
                ],
            );
        });

        return [$categories, $brands, $suppliers];
    }

    protected function seedCatalog(Collection $categories, Collection $brands, Collection $suppliers): Collection
    {
        $productNames = [
            'Classic Crew Tee', 'Tailored Oxford Shirt', 'Relaxed Denim Jean', 'Softline Midi Dress',
            'Utility Bomber Jacket', 'Studio Knit Sweater', 'Everyday Chino Pant', 'Harbor Polo Shirt',
            'Contour Running Sneaker', 'Ribbed Tank', 'Layered Hoodie', 'Coastal Linen Shirt',
            'Structured Blazer', 'Weekend Jogger', 'Pleated Skirt', 'Performance Legging',
            'Canvas Tote', 'Leather Belt', 'Wool Blend Coat', 'Active Track Jacket',
        ];

        $colorPalette = [
            'Black' => '#111827',
            'White' => '#F9FAFB',
            'Navy' => '#1D3557',
            'Olive' => '#556B2F',
            'Stone' => '#A8A29E',
            'Sand' => '#C2B280',
            'Burgundy' => '#800020',
            'Sky' => '#60A5FA',
        ];

        $seasonGroups = [
            ['spring', 'summer'],
            ['autumn', 'winter'],
            ['spring', 'autumn'],
            ['summer', 'winter'],
        ];

        $sizeSets = [
            'Footwear' => ['38', '40', '42'],
            'Accessories' => ['One Size'],
            'default' => ['S', 'M', 'L'],
        ];

        $variants = collect();
        $variantSequence = 1;

        for ($index = 1; $index <= 100; $index++) {
            /** @var Category $category */
            $category = $categories->random();
            /** @var Brand $brand */
            $brand = $brands->random();

            $name = $productNames[array_rand($productNames)].' '.str_pad((string) $index, 3, '0', STR_PAD_LEFT);
            $product = Product::query()->create([
                'product_code' => sprintf('PRD-%04d', $index),
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'name' => $name,
                'slug' => Str::slug($name.'-'.sprintf('%04d', $index)),
                'target_gender' => Arr::random(array_keys(Product::genderOptions())),
                'article_type' => $category->name,
                'status' => $index % 15 === 0 ? Product::STATUS_DRAFT : Product::STATUS_ACTIVE,
                'base_cost' => fake()->randomFloat(2, 12, 70),
                'retail_price' => fake()->randomFloat(2, 35, 180),
                'description' => fake()->sentence(12),
                'image_url' => null,
            ]);

            $selectedColors = collect($colorPalette)->random($category->name === 'Accessories' ? 2 : 3, preserveKeys: true);
            $selectedSeasons = $seasonGroups[array_rand($seasonGroups)];
            $selectedSizes = $sizeSets[$category->name] ?? $sizeSets['default'];

            foreach ($selectedSeasons as $season) {
                foreach ($selectedColors as $colorName => $hexCode) {
                    foreach ($selectedSizes as $size) {
                        /** @var Supplier $supplier */
                        $supplier = $suppliers->random();

                        $variant = ProductVariant::query()->create([
                            'product_id' => $product->id,
                            'supplier_id' => $supplier->id,
                            'sku' => sprintf('SKU-%04d-%s-%s-%s', $index, Str::upper(substr($colorName, 0, 3)), Str::upper(str_replace(' ', '', $size)), Str::upper(substr($season, 0, 2))),
                            'barcode' => sprintf('%012d', 800000000000 + $variantSequence),
                            'size' => $size,
                            'color_name' => $colorName,
                            'color_code' => $hexCode,
                            'season' => $season,
                            'unit_cost' => fake()->randomFloat(2, 10, 65),
                            'sale_price' => fake()->randomFloat(2, 30, 190),
                            'is_active' => true,
                        ]);

                        $reorderPoint = random_int(12, 28);
                        $onHand = random_int(8, 140);

                        if (random_int(1, 5) === 1) {
                            $onHand = random_int(2, $reorderPoint);
                        }

                        $variant->inventory()->updateOrCreate([], [
                            'on_hand' => $onHand,
                            'reserved' => random_int(0, 6),
                            'reorder_point' => $reorderPoint,
                            'reorder_quantity' => random_int(18, 60),
                            'safety_stock' => random_int(4, 10),
                            'last_restocked_at' => now()->subDays(random_int(5, 90)),
                            'last_counted_at' => now()->subDays(random_int(1, 30)),
                        ]);

                        $variants->push($variant->fresh(['inventory', 'product', 'supplier']));
                        $variantSequence++;
                    }
                }
            }
        }

        return $variants;
    }

    protected function seedPurchaseOrders(
        Collection $variants,
        Collection $users,
        Collection $suppliers,
        InventoryService $inventoryService,
    ): void {
        for ($index = 1; $index <= 24; $index++) {
            /** @var Supplier $supplier */
            $supplier = $suppliers->random();
            /** @var User $orderedBy */
            $orderedBy = $users->where('role', '!=', User::ROLE_VIEWER)->random();
            $status = Arr::random([
                PurchaseOrder::STATUS_ORDERED,
                PurchaseOrder::STATUS_ORDERED,
                PurchaseOrder::STATUS_RECEIVED,
                PurchaseOrder::STATUS_DRAFT,
            ]);

            $purchaseOrder = PurchaseOrder::query()->create([
                'order_number' => sprintf('PO-%s-%03d', now()->format('Ymd'), $index),
                'supplier_id' => $supplier->id,
                'ordered_by' => $orderedBy->id,
                'ordered_at' => now()->subDays(random_int(10, 120)),
                'expected_at' => now()->addDays(random_int(3, 18)),
                'status' => $status === PurchaseOrder::STATUS_RECEIVED ? PurchaseOrder::STATUS_ORDERED : $status,
                'shipping_cost' => random_int(20, 80),
                'notes' => 'Seasonal replenishment order.',
            ]);

            $supplierVariants = $variants->where('supplier_id', $supplier->id)->shuffle()->take(random_int(3, 7));

            foreach ($supplierVariants as $variant) {
                PurchaseOrderItem::query()->create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_variant_id' => $variant->id,
                    'ordered_quantity' => random_int(12, 45),
                    'received_quantity' => 0,
                    'unit_cost' => $variant->unit_cost,
                ]);
            }

            $purchaseOrder->syncTotals();

            if ($status === PurchaseOrder::STATUS_RECEIVED) {
                $inventoryService->receivePurchaseOrder($purchaseOrder, $orderedBy);
            }
        }
    }

    protected function seedSalesOrders(Collection $variants, Collection $users, InventoryService $inventoryService): void
    {
        for ($index = 1; $index <= 90; $index++) {
            /** @var User $soldBy */
            $soldBy = $users->where('role', '!=', User::ROLE_VIEWER)->random();
            $status = Arr::random([
                SalesOrder::STATUS_COMPLETED,
                SalesOrder::STATUS_COMPLETED,
                SalesOrder::STATUS_COMPLETED,
                SalesOrder::STATUS_DRAFT,
            ]);

            $salesOrder = SalesOrder::query()->create([
                'order_number' => sprintf('SO-%s-%04d', now()->format('Ymd'), $index),
                'sold_by' => $soldBy->id,
                'sold_at' => now()->subDays(random_int(1, 180))->setTime(random_int(9, 20), random_int(0, 59)),
                'sales_channel' => Arr::random(array_keys(SalesOrder::channelOptions())),
                'status' => SalesOrder::STATUS_DRAFT,
                'discount_amount' => random_int(0, 20),
                'notes' => 'Customer sale transaction.',
            ]);

            $saleVariants = $variants->shuffle()->take(random_int(2, 5));

            foreach ($saleVariants as $variant) {
                SalesOrderItem::query()->create([
                    'sales_order_id' => $salesOrder->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => random_int(1, 3),
                    'unit_price' => $variant->sale_price,
                ]);
            }

            $salesOrder->syncTotals();

            if ($status === SalesOrder::STATUS_COMPLETED) {
                try {
                    $inventoryService->completeSalesOrder($salesOrder, $soldBy);
                } catch (\DomainException) {
                    $salesOrder->delete();
                }
            }
        }
    }

    protected function seedManualAdjustments(Collection $variants, Collection $users, InventoryService $inventoryService): void
    {
        /** @var User $manager */
        $manager = $users->firstWhere('role', User::ROLE_MANAGER) ?? $users->first();

        $variants->shuffle()->take(24)->each(function (ProductVariant $variant) use ($inventoryService, $manager): void {
            $quantityChange = Arr::random([-4, -2, 3, 5, 7]);

            $inventory = $variant->inventory;

            if (! $inventory) {
                return;
            }

            if ($inventory->on_hand + $quantityChange < 0) {
                return;
            }

            $inventoryService->adjustInventory(
                inventory: $inventory,
                quantityChange: $quantityChange,
                type: StockMovement::TYPE_ADJUSTMENT,
                actor: $manager,
                referenceType: 'cycle_count',
                referenceNumber: 'COUNT-'.str_pad((string) $variant->id, 5, '0', STR_PAD_LEFT),
                notes: 'Cycle count variance adjustment.',
            );
        });
    }
}