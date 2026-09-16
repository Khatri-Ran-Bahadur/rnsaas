<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryLocation;
use Modules\Inventory\Models\InventoryStockAdjustment;
use Modules\Inventory\Models\InventoryUnit;
use Modules\Tenancy\Models\Tenant;

class InventoryDatabaseSeeder extends Seeder
{
    public function run(?int $targetTenantId = null): void
    {
        $tenants = $targetTenantId
            ? Tenant::where('id', $targetTenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $tenants = collect([(object) ['id' => $targetTenantId ?: 1]]);
        }

        foreach ($tenants as $tenant) {
            $tenantId = $tenant->id;

            // 1. Units of Measurement
            $pcs = InventoryUnit::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'pcs'],
                ['name' => 'Pieces', 'symbol' => 'pcs', 'is_base' => true, 'status' => 'active']
            );

            $box = InventoryUnit::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'box'],
                ['name' => 'Box', 'symbol' => 'box', 'is_base' => false, 'status' => 'active']
            );

            $carton = InventoryUnit::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'carton'],
                ['name' => 'Carton', 'symbol' => 'ctn', 'is_base' => false, 'status' => 'active']
            );

            $kg = InventoryUnit::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'kg'],
                ['name' => 'Kilogram', 'symbol' => 'kg', 'is_base' => true, 'status' => 'active']
            );

            $g = InventoryUnit::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'g'],
                ['name' => 'Gram', 'symbol' => 'g', 'is_base' => false, 'status' => 'active']
            );

            $l = InventoryUnit::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'l'],
                ['name' => 'Litre', 'symbol' => 'L', 'is_base' => true, 'status' => 'active']
            );

            $can = InventoryUnit::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'can'],
                ['name' => 'Can', 'symbol' => 'can', 'is_base' => true, 'status' => 'active']
            );

            // 2. Locations & Warehouses
            $locHq = InventoryLocation::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'WH-HQ-MAIN'],
                [
                    'name' => 'HQ - Main Warehouse',
                    'type' => 'warehouse',
                    'address' => 'Central Logistics Park, Bay A-12',
                    'status' => 'active',
                ]
            );

            $locCold = InventoryLocation::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'WH-HQ-CHL'],
                [
                    'name' => 'Central Chiller & Cold Storage',
                    'type' => 'cold_storage',
                    'address' => 'Cold Chain Facility Zone 4',
                    'status' => 'active',
                ]
            );

            $locKitchen = InventoryLocation::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'WH-HQ-KTN'],
                [
                    'name' => 'Main Kitchen Prep Station',
                    'type' => 'kitchen',
                    'address' => 'Level 1 Kitchen Line',
                    'status' => 'active',
                ]
            );

            $locOutlet = InventoryLocation::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'WH-RET-POS'],
                [
                    'name' => 'City Mall Outlet Store & POS',
                    'type' => 'retail',
                    'address' => 'City Mall Ground Floor Unit G-14',
                    'status' => 'active',
                ]
            );

            // 3. Categories with Hierarchy
            $catFastFood = InventoryCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'CAT-FF'],
                [
                    'name' => 'Fast Food',
                    'slug' => 'fast-food',
                    'parent_id' => null,
                    'status' => 'active',
                    'item_count' => 28,
                    'description' => 'Fast food, hot meals, burgers, pizzas, and sides.',
                ]
            );

            $catBurgers = InventoryCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'CAT-FF-BGR'],
                [
                    'name' => 'Burgers',
                    'slug' => 'burgers',
                    'parent_id' => $catFastFood->id,
                    'status' => 'active',
                    'item_count' => 12,
                    'description' => 'Gourmet and classic burger items.',
                ]
            );

            $catPizzas = InventoryCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'CAT-FF-PIZ'],
                [
                    'name' => 'Pizzas',
                    'slug' => 'pizzas',
                    'parent_id' => $catFastFood->id,
                    'status' => 'active',
                    'item_count' => 8,
                    'description' => 'Stone baked and artisanal pizzas.',
                ]
            );

            $catSides = InventoryCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'CAT-FF-SIDES'],
                [
                    'name' => 'Sides & Fries',
                    'slug' => 'sides',
                    'parent_id' => $catFastFood->id,
                    'status' => 'active',
                    'item_count' => 8,
                    'description' => 'Crispy sides, fries, onion rings, and wedges.',
                ]
            );

            $catBeverages = InventoryCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'CAT-BEV'],
                [
                    'name' => 'Beverages',
                    'slug' => 'beverages',
                    'parent_id' => null,
                    'status' => 'active',
                    'item_count' => 42,
                    'description' => 'Cold drinks, juices, coffee, and specialty drinks.',
                ]
            );

            $catSoftDrinks = InventoryCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'CAT-BEV-SOFT'],
                [
                    'name' => 'Soft Drinks',
                    'slug' => 'soft-drinks',
                    'parent_id' => $catBeverages->id,
                    'status' => 'active',
                    'item_count' => 16,
                    'description' => 'Canned and bottled sodas and carbonated beverages.',
                ]
            );

            $catCoffee = InventoryCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'CAT-BEV-HOT'],
                [
                    'name' => 'Hot Coffee & Tea',
                    'slug' => 'coffee-tea',
                    'parent_id' => $catBeverages->id,
                    'status' => 'active',
                    'item_count' => 12,
                    'description' => 'Barista brewed coffees, teas, and specialty lattes.',
                ]
            );

            $catRaw = InventoryCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'CAT-RAW'],
                [
                    'name' => 'Raw Ingredients & Kitchen Stock',
                    'slug' => 'raw-ingredients',
                    'parent_id' => null,
                    'status' => 'active',
                    'item_count' => 94,
                    'description' => 'Back-of-house raw ingredients, meats, dairy, and baking goods.',
                ]
            );

            $catMeat = InventoryCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'CAT-RAW-MEAT'],
                [
                    'name' => 'Poultry & Meat',
                    'slug' => 'meat',
                    'parent_id' => $catRaw->id,
                    'status' => 'active',
                    'item_count' => 24,
                    'description' => 'Raw poultry, prime beef patties, and fresh cuts.',
                ]
            );

            $catDairy = InventoryCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'CAT-RAW-DAIRY'],
                [
                    'name' => 'Dairy & Cheese',
                    'slug' => 'dairy',
                    'parent_id' => $catRaw->id,
                    'status' => 'active',
                    'item_count' => 18,
                    'description' => 'Cheese blocks, cheddar slices, butter, and cream.',
                ]
            );

            // 4. Inventory Products & Items
            $items = [
                [
                    'name' => 'Chicken Burger Deluxe',
                    'sku' => 'BURGER-001',
                    'barcode' => '9551234567890',
                    'category_id' => $catBurgers->id,
                    'unit_id' => $pcs->id,
                    'type' => 'stock',
                    'selling_price' => 15.50,
                    'cost_price' => 7.50,
                    'on_hand_stock' => 145.00,
                    'reserved_stock' => 10.00,
                    'reorder_point' => 30.00,
                    'status' => 'active',
                    'is_pos_available' => true,
                    'is_favorite' => true,
                    'track_stock' => true,
                    'tax_rate' => 8.00,
                    'modifier_groups' => [
                        [
                            'id' => 1,
                            'name' => 'Burger Doneness',
                            'required' => true,
                            'options' => [
                                ['name' => 'Medium Well', 'price' => 0.00],
                                ['name' => 'Well Done', 'price' => 0.00],
                            ],
                        ],
                        [
                            'id' => 2,
                            'name' => 'Add-ons',
                            'required' => false,
                            'options' => [
                                ['name' => 'Extra Cheddar Slice', 'price' => 2.50],
                                ['name' => 'Crispy Bacon Strip', 'price' => 3.50],
                                ['name' => 'Truffle Mayo Dip', 'price' => 2.00],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Wagyu Gourmet Beef Burger',
                    'sku' => 'BURGER-002',
                    'barcode' => '9551234567891',
                    'category_id' => $catBurgers->id,
                    'unit_id' => $pcs->id,
                    'type' => 'stock',
                    'selling_price' => 28.00,
                    'cost_price' => 14.00,
                    'on_hand_stock' => 85.00,
                    'reserved_stock' => 5.00,
                    'reorder_point' => 20.00,
                    'status' => 'active',
                    'is_pos_available' => true,
                    'is_favorite' => true,
                    'track_stock' => true,
                    'tax_rate' => 8.00,
                    'modifier_groups' => [
                        [
                            'id' => 1,
                            'name' => 'Patty Doneness',
                            'required' => true,
                            'options' => [
                                ['name' => 'Medium Rare', 'price' => 0.00],
                                ['name' => 'Medium', 'price' => 0.00],
                                ['name' => 'Well Done', 'price' => 0.00],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Crispy Truffle Fries',
                    'sku' => 'SNK-002',
                    'barcode' => '9551234567892',
                    'category_id' => $catSides->id,
                    'unit_id' => $pcs->id,
                    'type' => 'stock',
                    'selling_price' => 11.90,
                    'cost_price' => 4.50,
                    'on_hand_stock' => 120.00,
                    'reserved_stock' => 0.00,
                    'reorder_point' => 25.00,
                    'status' => 'active',
                    'is_pos_available' => true,
                    'is_favorite' => true,
                    'track_stock' => true,
                    'tax_rate' => 8.00,
                    'modifier_groups' => null,
                ],
                [
                    'name' => 'Signature Pepperoni Pizza 12"',
                    'sku' => 'PIZ-001',
                    'barcode' => '9551234567893',
                    'category_id' => $catPizzas->id,
                    'unit_id' => $pcs->id,
                    'type' => 'stock',
                    'selling_price' => 24.50,
                    'cost_price' => 10.00,
                    'on_hand_stock' => 65.00,
                    'reserved_stock' => 0.00,
                    'reorder_point' => 15.00,
                    'status' => 'active',
                    'is_pos_available' => true,
                    'is_favorite' => true,
                    'track_stock' => true,
                    'tax_rate' => 8.00,
                    'modifier_groups' => null,
                ],
                [
                    'name' => 'Coca Cola Can 320ml',
                    'sku' => 'BEV-001',
                    'barcode' => '9551234567894',
                    'category_id' => $catSoftDrinks->id,
                    'unit_id' => $can->id,
                    'type' => 'stock',
                    'selling_price' => 3.50,
                    'cost_price' => 1.50,
                    'on_hand_stock' => 250.00,
                    'reserved_stock' => 0.00,
                    'reorder_point' => 50.00,
                    'status' => 'active',
                    'is_pos_available' => true,
                    'is_favorite' => false,
                    'track_stock' => true,
                    'tax_rate' => 8.00,
                    'modifier_groups' => null,
                ],
                [
                    'name' => 'Caramel Iced Latte',
                    'sku' => 'BEV-004',
                    'barcode' => '9551234567895',
                    'category_id' => $catCoffee->id,
                    'unit_id' => $pcs->id,
                    'type' => 'stock',
                    'selling_price' => 13.00,
                    'cost_price' => 4.20,
                    'on_hand_stock' => 95.00,
                    'reserved_stock' => 0.00,
                    'reorder_point' => 20.00,
                    'status' => 'active',
                    'is_pos_available' => true,
                    'is_favorite' => true,
                    'track_stock' => true,
                    'tax_rate' => 8.00,
                    'modifier_groups' => [
                        [
                            'id' => 1,
                            'name' => 'Sugar Level',
                            'required' => true,
                            'options' => [
                                ['name' => 'Normal 100%', 'price' => 0.00],
                                ['name' => 'Less Sweet 50%', 'price' => 0.00],
                                ['name' => 'No Sugar 0%', 'price' => 0.00],
                            ],
                        ],
                        [
                            'id' => 2,
                            'name' => 'Milk Option',
                            'required' => false,
                            'options' => [
                                ['name' => 'Oat Milk Sub', 'price' => 2.00],
                                ['name' => 'Almond Milk Sub', 'price' => 2.00],
                            ],
                        ],
                    ],
                ],
                // Raw Materials
                [
                    'name' => 'Brioche Burger Buns (Pack of 12)',
                    'sku' => 'RAW-BUN-12',
                    'barcode' => '9551234567896',
                    'category_id' => $catRaw->id,
                    'unit_id' => $box->id,
                    'type' => 'raw_material',
                    'selling_price' => 0.00,
                    'cost_price' => 6.00,
                    'on_hand_stock' => 180.00,
                    'reserved_stock' => 0.00,
                    'reorder_point' => 40.00,
                    'status' => 'active',
                    'is_pos_available' => false,
                    'is_favorite' => false,
                    'track_stock' => true,
                    'tax_rate' => 0.00,
                    'modifier_groups' => null,
                ],
                [
                    'name' => 'Fresh Mozzarella Block 1kg',
                    'sku' => 'RAW-MOZZ-1KG',
                    'barcode' => '9551234567897',
                    'category_id' => $catDairy->id,
                    'unit_id' => $kg->id,
                    'type' => 'raw_material',
                    'selling_price' => 0.00,
                    'cost_price' => 28.00,
                    'on_hand_stock' => 45.00,
                    'reserved_stock' => 0.00,
                    'reorder_point' => 15.00,
                    'status' => 'active',
                    'is_pos_available' => false,
                    'is_favorite' => false,
                    'track_stock' => true,
                    'tax_rate' => 0.00,
                    'modifier_groups' => null,
                ],
                [
                    'name' => 'Chicken Breast Fillet 1kg',
                    'sku' => 'RAW-CHK-1KG',
                    'barcode' => '9551234567898',
                    'category_id' => $catMeat->id,
                    'unit_id' => $kg->id,
                    'type' => 'raw_material',
                    'selling_price' => 0.00,
                    'cost_price' => 18.50,
                    'on_hand_stock' => 110.00,
                    'reserved_stock' => 0.00,
                    'reorder_point' => 30.00,
                    'status' => 'active',
                    'is_pos_available' => false,
                    'is_favorite' => false,
                    'track_stock' => true,
                    'tax_rate' => 0.00,
                    'modifier_groups' => null,
                ],
            ];

            $createdItems = [];
            foreach ($items as $itemData) {
                $item = InventoryItem::updateOrCreate(
                    ['tenant_id' => $tenantId, 'sku' => $itemData['sku']],
                    $itemData
                );
                $createdItems[] = $item;
            }

            // 5. Stock Adjustments (Audit Log)
            if (! empty($createdItems)) {
                $firstItem = $createdItems[0];
                InventoryStockAdjustment::updateOrCreate(
                    ['tenant_id' => $tenantId, 'reason' => 'Initial Stock Opening Count'],
                    [
                        'item_id' => $firstItem->id,
                        'location_id' => $locHq->id,
                        'type' => 'addition',
                        'quantity' => 100,
                        'adjusted_by' => 'Ran Bahadur Khatri',
                    ]
                );
            }
        }
    }
}
