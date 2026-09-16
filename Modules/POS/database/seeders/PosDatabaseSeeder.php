<?php

namespace Modules\POS\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Inventory\Models\InventoryItem;
use Modules\POS\Models\PosCashMovement;
use Modules\POS\Models\PosKitchenStation;
use Modules\POS\Models\PosKitchenTicket;
use Modules\POS\Models\PosKitchenTicketItem;
use Modules\POS\Models\PosOrder;
use Modules\POS\Models\PosOrderItem;
use Modules\POS\Models\PosRegister;
use Modules\POS\Models\PosShift;
use Modules\POS\Models\PosTable;
use Modules\Tenancy\Models\Tenant;

class PosDatabaseSeeder extends Seeder
{
    public function run(?int $targetTenantId = null): void
    {
        $tenants = $targetTenantId
            ? Tenant::where('id', $targetTenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            return;
        }

        foreach ($tenants as $tenant) {
            $user = User::first() ?? User::factory()->create();
            $userId = $user->id;
            $userName = $user->name;

            // 1. Registers
            $reg1 = PosRegister::firstOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'REG-01'],
                [
                    'name' => 'Counter 01 - Main POS',
                    'location' => 'Main Dining Hall Entrance',
                    'receipt_printer_ip' => '192.168.1.180',
                    'is_active' => true,
                ]
            );

            $reg2 = PosRegister::firstOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'REG-02'],
                [
                    'name' => 'Counter 02 - Express POS',
                    'location' => 'Takeaway & Bar Counter',
                    'receipt_printer_ip' => '192.168.1.181',
                    'is_active' => true,
                ]
            );

            // 2. Active Shift & Movements
            $shift = PosShift::firstOrCreate(
                ['tenant_id' => $tenant->id, 'shift_number' => 'SH-'.date('Ymd').'-01'],
                [
                    'register_id' => $reg1->id,
                    'user_id' => $userId,
                    'terminal_name' => $reg1->name,
                    'opened_at' => now()->startOfDay()->addHours(8),
                    'closed_at' => null,
                    'opening_float' => 200.00,
                    'cash_sales' => 1120.00,
                    'card_sales' => 840.50,
                    'qr_sales' => 490.30,
                    'total_sales' => 2450.80,
                    'cash_in' => 50.00,
                    'cash_out' => 20.00,
                    'expected_cash' => 1350.00,
                    'actual_cash' => null,
                    'variance' => 0.00,
                    'transaction_count' => 38,
                    'status' => 'open',
                    'notes' => 'Morning shift handover completed cleanly.',
                ]
            );

            if ($shift->wasRecentlyCreated) {
                PosCashMovement::create([
                    'tenant_id' => $tenant->id,
                    'shift_id' => $shift->id,
                    'user_id' => $userId,
                    'type' => 'opening_float',
                    'amount' => 200.00,
                    'reason' => 'Initial drawer float setup',
                    'authorized_by' => $userName,
                ]);

                PosCashMovement::create([
                    'tenant_id' => $tenant->id,
                    'shift_id' => $shift->id,
                    'user_id' => $userId,
                    'type' => 'cash_in',
                    'amount' => 50.00,
                    'reason' => 'Small change replenishment (Coins & RM1 notes)',
                    'authorized_by' => $userName,
                ]);

                PosCashMovement::create([
                    'tenant_id' => $tenant->id,
                    'shift_id' => $shift->id,
                    'user_id' => $userId,
                    'type' => 'cash_out',
                    'amount' => -20.00,
                    'reason' => 'Petty cash payout: Emergency ice delivery',
                    'authorized_by' => $userName,
                ]);
            }

            // Past Shifts
            PosShift::firstOrCreate(
                ['tenant_id' => $tenant->id, 'shift_number' => 'SH-'.date('Ymd', strtotime('-1 day')).'-02'],
                [
                    'register_id' => $reg1->id,
                    'user_id' => $userId,
                    'terminal_name' => $reg1->name,
                    'opened_at' => now()->subDay()->setTime(16, 0),
                    'closed_at' => now()->subDay()->setTime(23, 30),
                    'opening_float' => 200.00,
                    'cash_sales' => 1750.00,
                    'card_sales' => 1240.00,
                    'qr_sales' => 900.00,
                    'total_sales' => 3890.00,
                    'cash_in' => 0.00,
                    'cash_out' => 0.00,
                    'expected_cash' => 1950.00,
                    'actual_cash' => 1950.00,
                    'variance' => 0.00,
                    'transaction_count' => 54,
                    'status' => 'closed',
                    'verified_by' => $userId,
                ]
            );

            // 3. Restaurant Tables Floor Plan
            $tablesData = [
                ['table_number' => 'T-01', 'name' => 'Table 01', 'section' => 'main', 'capacity' => 2, 'shape' => 'square', 'status' => 'available', 'x_pos' => 50, 'y_pos' => 50],
                ['table_number' => 'T-02', 'name' => 'Table 02', 'section' => 'main', 'capacity' => 4, 'shape' => 'square', 'status' => 'occupied', 'x_pos' => 180, 'y_pos' => 50, 'current_order_ref' => 'ORD-9801', 'current_order_total' => 64.50, 'occupied_minutes' => 35, 'assigned_server' => 'Farhan'],
                ['table_number' => 'T-03', 'name' => 'Table 03 (Booth)', 'section' => 'main', 'capacity' => 6, 'shape' => 'rectangle', 'status' => 'occupied', 'x_pos' => 320, 'y_pos' => 50, 'current_order_ref' => 'ORD-9807', 'current_order_total' => 148.00, 'occupied_minutes' => 50, 'assigned_server' => 'Siti'],
                ['table_number' => 'T-04', 'name' => 'Table 04', 'section' => 'main', 'capacity' => 4, 'shape' => 'square', 'status' => 'billing', 'x_pos' => 50, 'y_pos' => 180, 'current_order_ref' => 'ORD-9802', 'current_order_total' => 88.20, 'occupied_minutes' => 65, 'assigned_server' => 'Farhan'],
                ['table_number' => 'TER-01', 'name' => 'Terrace T-01', 'section' => 'terrace', 'capacity' => 4, 'shape' => 'round', 'status' => 'occupied', 'x_pos' => 180, 'y_pos' => 180, 'current_order_ref' => 'ORD-9810', 'current_order_total' => 42.00, 'occupied_minutes' => 12, 'assigned_server' => 'Farhan'],
                ['table_number' => 'TER-02', 'name' => 'Terrace T-02', 'section' => 'terrace', 'capacity' => 4, 'shape' => 'round', 'status' => 'available', 'x_pos' => 320, 'y_pos' => 180],
                ['table_number' => 'BAR-01', 'name' => 'Bar High Top 1', 'section' => 'bar', 'capacity' => 2, 'shape' => 'round', 'status' => 'available', 'x_pos' => 50, 'y_pos' => 300],
                ['table_number' => 'VIP-01', 'name' => 'VIP Private Room', 'section' => 'vip', 'capacity' => 10, 'shape' => 'rectangle', 'status' => 'reserved', 'x_pos' => 200, 'y_pos' => 300],
            ];

            foreach ($tablesData as $t) {
                PosTable::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'table_number' => $t['table_number']],
                    $t
                );
            }

            // 4. Sample Orders
            $burger1 = InventoryItem::where('tenant_id', $tenant->id)->where('sku', 'BURGER-001')->first();
            $burger2 = InventoryItem::where('tenant_id', $tenant->id)->where('sku', 'BURGER-002')->first();
            $fries = InventoryItem::where('tenant_id', $tenant->id)->where('sku', 'SNK-002')->first();
            $pizza = InventoryItem::where('tenant_id', $tenant->id)->where('sku', 'PIZ-001')->first();
            $coke = InventoryItem::where('tenant_id', $tenant->id)->where('sku', 'BEV-001')->first();
            $latte = InventoryItem::where('tenant_id', $tenant->id)->where('sku', 'BEV-004')->first();

            $orderDataList = [
                [
                    'order_number' => 'ORD-9810',
                    'order_type' => 'dine_in',
                    'customer_name' => 'Table Guest (Terrace T-01)',
                    'subtotal' => 43.40,
                    'tax_total' => 3.47,
                    'discount_total' => 0.00,
                    'grand_total' => 46.87,
                    'paid_amount' => 50.00,
                    'change_amount' => 3.13,
                    'payment_method' => 'cash',
                    'status' => 'completed',
                    'cashier_name' => 'Farhan',
                    'created_at' => now()->setTime(10, 30),
                    'items' => [
                        ['product' => $burger1, 'name' => 'Chicken Burger Deluxe', 'sku' => 'BURGER-001', 'qty' => 2, 'price' => 15.50, 'total' => 31.00],
                        ['product' => $fries, 'name' => 'Crispy Truffle Fries', 'sku' => 'SNK-002', 'qty' => 1, 'price' => 11.90, 'total' => 11.90],
                    ],
                ],
                [
                    'order_number' => 'ORD-9811',
                    'order_type' => 'dine_in',
                    'customer_name' => 'Ahmad Razak',
                    'subtotal' => 74.00,
                    'tax_total' => 5.92,
                    'discount_total' => 5.00,
                    'grand_total' => 74.92,
                    'paid_amount' => 74.92,
                    'change_amount' => 0.00,
                    'payment_method' => 'card',
                    'status' => 'completed',
                    'cashier_name' => 'Siti',
                    'created_at' => now()->setTime(12, 15),
                    'items' => [
                        ['product' => $burger2, 'name' => 'Wagyu Gourmet Beef Burger', 'sku' => 'BURGER-002', 'qty' => 2, 'price' => 28.00, 'total' => 56.00],
                        ['product' => $latte, 'name' => 'Caramel Iced Latte', 'sku' => 'BEV-004', 'qty' => 1, 'price' => 13.00, 'total' => 13.00],
                        ['product' => $coke, 'name' => 'Coca Cola Can 320ml', 'sku' => 'BEV-001', 'qty' => 1, 'price' => 3.50, 'total' => 3.50],
                    ],
                ],
                [
                    'order_number' => 'ORD-9812',
                    'order_type' => 'takeaway',
                    'customer_name' => 'Grace Tan',
                    'subtotal' => 49.00,
                    'tax_total' => 3.92,
                    'discount_total' => 0.00,
                    'grand_total' => 52.92,
                    'paid_amount' => 52.92,
                    'change_amount' => 0.00,
                    'payment_method' => 'qr_code',
                    'status' => 'completed',
                    'cashier_name' => 'Farhan',
                    'created_at' => now()->setTime(13, 00),
                    'items' => [
                        ['product' => $pizza, 'name' => 'Signature Pepperoni Pizza 12"', 'sku' => 'PIZ-001', 'qty' => 2, 'price' => 24.50, 'total' => 49.00],
                    ],
                ],
                [
                    'order_number' => 'ORD-9813',
                    'order_type' => 'dine_in',
                    'customer_name' => 'Kevin Lee',
                    'subtotal' => 56.50,
                    'tax_total' => 4.52,
                    'discount_total' => 0.00,
                    'grand_total' => 61.02,
                    'paid_amount' => 61.02,
                    'change_amount' => 0.00,
                    'payment_method' => 'card',
                    'status' => 'completed',
                    'cashier_name' => $userName,
                    'created_at' => now()->setTime(14, 30),
                    'items' => [
                        ['product' => $burger2, 'name' => 'Wagyu Gourmet Beef Burger', 'sku' => 'BURGER-002', 'qty' => 1, 'price' => 28.00, 'total' => 28.00],
                        ['product' => $burger1, 'name' => 'Chicken Burger Deluxe', 'sku' => 'BURGER-001', 'qty' => 1, 'price' => 15.50, 'total' => 15.50],
                        ['product' => $latte, 'name' => 'Caramel Iced Latte', 'sku' => 'BEV-004', 'qty' => 1, 'price' => 13.00, 'total' => 13.00],
                    ],
                ],
                [
                    'order_number' => 'ORD-9814',
                    'order_type' => 'takeaway',
                    'customer_name' => 'Sarah Wong',
                    'subtotal' => 32.50,
                    'tax_total' => 2.60,
                    'discount_total' => 2.00,
                    'grand_total' => 33.10,
                    'paid_amount' => 40.00,
                    'change_amount' => 6.90,
                    'payment_method' => 'cash',
                    'status' => 'completed',
                    'cashier_name' => 'Siti',
                    'created_at' => now()->setTime(18, 10),
                    'items' => [
                        ['product' => $burger1, 'name' => 'Chicken Burger Deluxe', 'sku' => 'BURGER-001', 'qty' => 1, 'price' => 15.50, 'total' => 15.50],
                        ['product' => $fries, 'name' => 'Crispy Truffle Fries', 'sku' => 'SNK-002', 'qty' => 1, 'price' => 11.90, 'total' => 11.90],
                        ['product' => $coke, 'name' => 'Coca Cola Can 320ml', 'sku' => 'BEV-001', 'qty' => 1, 'price' => 3.50, 'total' => 3.50],
                    ],
                ],
            ];

            $order1 = null;
            foreach ($orderDataList as $od) {
                $itemsData = $od['items'];
                unset($od['items']);

                $order = PosOrder::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'order_number' => $od['order_number']],
                    array_merge($od, [
                        'shift_id' => $shift->id,
                        'register_id' => $reg1->id,
                        'user_id' => $userId,
                    ])
                );

                if (! $order1) {
                    $order1 = $order;
                }

                if ($order->wasRecentlyCreated) {
                    foreach ($itemsData as $itemInfo) {
                        PosOrderItem::create([
                            'tenant_id' => $tenant->id,
                            'order_id' => $order->id,
                            'product_id' => $itemInfo['product']?->id,
                            'item_name' => $itemInfo['name'],
                            'item_code' => $itemInfo['sku'],
                            'quantity' => $itemInfo['qty'],
                            'unit_price' => $itemInfo['price'],
                            'discount_amount' => 0.00,
                            'tax_amount' => round($itemInfo['price'] * 0.08, 2),
                            'total_price' => $itemInfo['total'],
                        ]);
                    }
                }
            }

            // 5. Kitchen Stations (KDS)
            $stationsData = [
                ['code' => 'kitchen', 'name' => 'Main Hot Kitchen', 'icon' => 'Flame', 'display_order' => 1, 'is_active' => true],
                ['code' => 'bar', 'name' => 'Bar & Coffee Station', 'icon' => 'Coffee', 'display_order' => 2, 'is_active' => true],
                ['code' => 'grill', 'name' => 'Grill & Fryer Station', 'icon' => 'Utensils', 'display_order' => 3, 'is_active' => true],
                ['code' => 'bakery', 'name' => 'Bakery & Dessert Station', 'icon' => 'Cake', 'display_order' => 4, 'is_active' => true],
            ];

            foreach ($stationsData as $sData) {
                PosKitchenStation::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'code' => $sData['code']],
                    $sData
                );
            }

            // 6. KDS Kitchen Tickets
            $ticketsData = [
                [
                    'ticket_number' => 'KOT-1092',
                    'order_ref' => 'ORD-9810',
                    'order_id' => $order1->id,
                    'order_type' => 'dine_in',
                    'destination' => 'Terrace T-01',
                    'station' => 'kitchen',
                    'priority' => 'rush',
                    'elapsed_seconds' => 360,
                    'status' => 'new',
                    'server_name' => 'Farhan',
                    'notes' => 'Customer requested food served together.',
                    'items' => [
                        ['name' => 'Chicken Deluxe Burger', 'quantity' => 1, 'modifiers' => ['Extra Cheddar Cheese (+RM2.00)', 'No Raw Onion'], 'notes' => 'Make it well toasted.', 'is_done' => false],
                        ['name' => 'Golden French Fries (Large)', 'quantity' => 1, 'modifiers' => ['Smoked Paprika & Garlic Shake'], 'notes' => '', 'is_done' => false],
                    ],
                ],
                [
                    'ticket_number' => 'KOT-1090',
                    'order_ref' => 'ORD-9807',
                    'order_type' => 'dine_in',
                    'destination' => 'Table 06 (Booth)',
                    'station' => 'grill',
                    'priority' => 'vip',
                    'elapsed_seconds' => 840,
                    'status' => 'preparing',
                    'server_name' => 'Siti',
                    'notes' => 'Table has baby in high chair.',
                    'items' => [
                        ['name' => 'Wagyu Gourmet Beef Burger', 'quantity' => 2, 'modifiers' => ['Medium (Recommended)', 'Black Truffle Mayo (+RM4.00)'], 'notes' => 'Separate sauces on side please.', 'is_done' => true],
                        ['name' => 'Buffalo Spicy Chicken Wings (6 pcs)', 'quantity' => 1, 'modifiers' => [], 'notes' => '', 'is_done' => false],
                    ],
                ],
                [
                    'ticket_number' => 'KOT-1089',
                    'order_ref' => 'ORD-9805',
                    'order_type' => 'takeaway',
                    'destination' => 'Takeaway #14 (Counter Pickup)',
                    'station' => 'bar',
                    'priority' => 'normal',
                    'elapsed_seconds' => 1140,
                    'status' => 'ready',
                    'server_name' => 'Cashier 01',
                    'notes' => 'Pack in beverage cup carrier.',
                    'items' => [
                        ['name' => 'Handcrafted Iced Latte', 'quantity' => 2, 'modifiers' => ['Oat Milk (Barista Edition) (+RM2.50)', 'Less Sweet (50%)'], 'notes' => '', 'is_done' => true],
                        ['name' => 'Signature Fresh Lemon Iced Tea', 'quantity' => 1, 'modifiers' => [], 'notes' => 'Extra ice.', 'is_done' => true],
                    ],
                ],
                [
                    'ticket_number' => 'KOT-1088',
                    'order_ref' => 'ORD-9801',
                    'order_type' => 'dine_in',
                    'destination' => 'Table 01',
                    'station' => 'kitchen',
                    'priority' => 'normal',
                    'elapsed_seconds' => 2040,
                    'status' => 'served',
                    'server_name' => 'Farhan',
                    'notes' => '',
                    'items' => [
                        ['name' => 'Crispy Zinger Chicken Wrap', 'quantity' => 3, 'modifiers' => [], 'notes' => '', 'is_done' => true],
                    ],
                ],
            ];

            foreach ($ticketsData as $tk) {
                $items = $tk['items'];
                unset($tk['items']);

                $ticket = PosKitchenTicket::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'ticket_number' => $tk['ticket_number']],
                    $tk
                );

                if ($ticket->wasRecentlyCreated) {
                    foreach ($items as $item) {
                        PosKitchenTicketItem::create([
                            'ticket_id' => $ticket->id,
                            'name' => $item['name'],
                            'quantity' => $item['quantity'],
                            'modifiers' => $item['modifiers'],
                            'notes' => $item['notes'],
                            'is_done' => $item['is_done'],
                        ]);
                    }
                }
            }
        }
    }
}
