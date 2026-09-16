<?php

namespace Modules\MRP\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\MRP\Models\MrpBom;
use Modules\MRP\Models\MrpBomItem;
use Modules\MRP\Models\MrpFinishedGood;
use Modules\MRP\Models\MrpMaterialIssue;
use Modules\MRP\Models\MrpQualityInspection;
use Modules\MRP\Models\MrpWorkCenter;
use Modules\MRP\Models\MrpWorkOrder;
use Modules\Tenancy\Models\Tenant;

class MrpDatabaseSeeder extends Seeder
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

            // 1. Work Centers
            $wcOven = MrpWorkCenter::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'WC-BAK-OVEN'],
                [
                    'name' => 'Oven Deck Line A',
                    'branch' => 'Central Bakery Hub',
                    'type' => 'machine',
                    'capacity_hours_per_day' => 16.00,
                    'hourly_rate' => 45.00,
                    'hourly_cost' => 45.00,
                    'efficiency_percentage' => 92,
                    'oee_percentage' => 86,
                    'utilization_percent' => 88,
                    'status' => 'operational',
                    'machines_count' => 2,
                ]
            );

            $wcButchery = MrpWorkCenter::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'WC-CK-BUTCH'],
                [
                    'name' => 'Butchery & Portioning',
                    'branch' => 'Central Kitchen HQ',
                    'type' => 'manual_station',
                    'capacity_hours_per_day' => 12.00,
                    'hourly_rate' => 35.00,
                    'hourly_cost' => 35.00,
                    'efficiency_percentage' => 88,
                    'oee_percentage' => 78,
                    'utilization_percent' => 62,
                    'status' => 'idle',
                    'machines_count' => 3,
                ]
            );

            $wcSmt = MrpWorkCenter::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'WC-ELC-SMT'],
                [
                    'name' => 'SMT Line 2',
                    'branch' => 'Main Electronics Plant',
                    'type' => 'assembly_line',
                    'capacity_hours_per_day' => 20.00,
                    'hourly_rate' => 85.00,
                    'hourly_cost' => 85.00,
                    'efficiency_percentage' => 96,
                    'oee_percentage' => 91,
                    'utilization_percent' => 94,
                    'status' => 'operational',
                    'machines_count' => 4,
                ]
            );

            $wcAssy = MrpWorkCenter::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'WC-FUR-ASSY'],
                [
                    'name' => 'Assembly Bay 4',
                    'branch' => 'Furniture Assembly Facility',
                    'type' => 'manual_station',
                    'capacity_hours_per_day' => 8.00,
                    'hourly_rate' => 25.00,
                    'hourly_cost' => 25.00,
                    'efficiency_percentage' => 80,
                    'oee_percentage' => 65,
                    'utilization_percent' => 45,
                    'status' => 'idle',
                    'machines_count' => 1,
                ]
            );

            $wcCnc = MrpWorkCenter::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'WC-CNC-MILL'],
                [
                    'name' => 'CNC Milling & Facing Bay',
                    'branch' => 'Precision Machining Plant',
                    'type' => 'machine',
                    'capacity_hours_per_day' => 16.00,
                    'hourly_rate' => 65.00,
                    'hourly_cost' => 65.00,
                    'efficiency_percentage' => 70,
                    'oee_percentage' => 58,
                    'utilization_percent' => 30,
                    'status' => 'maintenance',
                    'machines_count' => 1,
                ]
            );

            // 2. Bills of Materials (BOM)
            $bomSourdough = MrpBom::updateOrCreate(
                ['tenant_id' => $tenantId, 'bom_number' => 'BOM-2026-001'],
                [
                    'product_name' => 'Artisan Sourdough Loaf 500g',
                    'sku' => 'BAK-SRD-500',
                    'bom_type' => 'Recipe BOM',
                    'version' => 'v2.1',
                    'output_qty' => 100.00,
                    'unit' => 'Loaf',
                    'estimated_cost' => 184.5000,
                    'unit_cost' => 1.8450,
                    'status' => 'Active',
                    'is_active' => true,
                    'is_default' => true,
                    'routing_name' => 'Bakery Oven Line 1',
                    'levels_count' => 1,
                    'effective_from' => '2026-01-01',
                    'created_by' => 'Chef Pierre',
                    'notes' => 'Fermented sourdough formula with slow 24h cold retard.',
                ]
            );

            MrpBomItem::updateOrCreate(
                ['tenant_id' => $tenantId, 'bom_id' => $bomSourdough->id, 'sku' => 'RM-FLOUR-T65'],
                ['name' => 'Organic Bread Flour T65', 'quantity' => 50.0000, 'unit' => 'kg', 'unit_cost' => 1.2000]
            );
            MrpBomItem::updateOrCreate(
                ['tenant_id' => $tenantId, 'bom_id' => $bomSourdough->id, 'sku' => 'RM-STARTER'],
                ['name' => 'Natural Sourdough Starter', 'quantity' => 15.0000, 'unit' => 'kg', 'unit_cost' => 2.5000]
            );
            MrpBomItem::updateOrCreate(
                ['tenant_id' => $tenantId, 'bom_id' => $bomSourdough->id, 'sku' => 'RM-WATER'],
                ['name' => 'Purified Mineral Water', 'quantity' => 35.0000, 'unit' => 'L', 'unit_cost' => 0.0500]
            );
            MrpBomItem::updateOrCreate(
                ['tenant_id' => $tenantId, 'bom_id' => $bomSourdough->id, 'sku' => 'RM-SALT'],
                ['name' => 'Fine Sea Salt', 'quantity' => 1.0000, 'unit' => 'kg', 'unit_cost' => 0.8000]
            );

            $bomBurger = MrpBom::updateOrCreate(
                ['tenant_id' => $tenantId, 'bom_number' => 'BOM-2026-002'],
                [
                    'product_name' => 'Signature Double Cheese Burger Combo',
                    'sku' => 'CK-BURGER-DBL',
                    'bom_type' => 'Production BOM',
                    'version' => 'v3.0',
                    'output_qty' => 1.00,
                    'unit' => 'Set',
                    'estimated_cost' => 8.4200,
                    'unit_cost' => 8.4200,
                    'status' => 'Active',
                    'is_active' => true,
                    'is_default' => true,
                    'routing_name' => 'Hot Kitchen Assembly Bay',
                    'levels_count' => 2,
                    'effective_from' => '2026-02-15',
                    'created_by' => 'Executive Kitchen Lead',
                ]
            );

            MrpBomItem::updateOrCreate(
                ['tenant_id' => $tenantId, 'bom_id' => $bomBurger->id, 'sku' => 'ING-BUN-BRIOCHE'],
                ['name' => 'Toasted Brioche Bun', 'quantity' => 1.0000, 'unit' => 'Pcs', 'unit_cost' => 1.2000]
            );
            MrpBomItem::updateOrCreate(
                ['tenant_id' => $tenantId, 'bom_id' => $bomBurger->id, 'sku' => 'SUB-PATTY-CKD'],
                ['name' => 'Cooked Beef Patty Subassembly', 'quantity' => 2.0000, 'unit' => 'Pcs', 'unit_cost' => 2.1000, 'is_subassembly' => true]
            );
            MrpBomItem::updateOrCreate(
                ['tenant_id' => $tenantId, 'bom_id' => $bomBurger->id, 'sku' => 'ING-CHEESE-CHED'],
                ['name' => 'Aged Cheddar Cheese Slice', 'quantity' => 2.0000, 'unit' => 'Slices', 'unit_cost' => 0.4000]
            );
            MrpBomItem::updateOrCreate(
                ['tenant_id' => $tenantId, 'bom_id' => $bomBurger->id, 'sku' => 'ING-SAUCE-SPEC'],
                ['name' => 'Secret House Burger Sauce', 'quantity' => 30.0000, 'unit' => 'ml', 'unit_cost' => 0.0150]
            );

            $bomIot = MrpBom::updateOrCreate(
                ['tenant_id' => $tenantId, 'bom_number' => 'BOM-2026-003'],
                [
                    'product_name' => 'Industrial IoT Gateway v3',
                    'sku' => 'ELC-IOT-GW3',
                    'bom_type' => 'Production BOM',
                    'version' => 'v3.0',
                    'output_qty' => 1.00,
                    'unit' => 'Unit',
                    'estimated_cost' => 42.5000,
                    'unit_cost' => 42.5000,
                    'status' => 'Active',
                    'is_active' => true,
                    'is_default' => true,
                    'routing_name' => 'SMT Line 2',
                    'levels_count' => 1,
                    'effective_from' => '2026-03-01',
                    'created_by' => 'Hardware Eng Team',
                ]
            );

            MrpBomItem::updateOrCreate(
                ['tenant_id' => $tenantId, 'bom_id' => $bomIot->id, 'sku' => 'PCB-MAIN-V3'],
                ['name' => 'Multi-layer Mainboard PCB', 'quantity' => 1.0000, 'unit' => 'Pcs', 'unit_cost' => 14.5000]
            );
            MrpBomItem::updateOrCreate(
                ['tenant_id' => $tenantId, 'bom_id' => $bomIot->id, 'sku' => 'IC-MCU-STM32'],
                ['name' => 'Cortex-M4 MCU IC', 'quantity' => 1.0000, 'unit' => 'Pcs', 'unit_cost' => 8.2000]
            );
            MrpBomItem::updateOrCreate(
                ['tenant_id' => $tenantId, 'bom_id' => $bomIot->id, 'sku' => 'MOD-LTE-4G'],
                ['name' => '4G/LTE Cellular Modem Module', 'quantity' => 1.0000, 'unit' => 'Pcs', 'unit_cost' => 12.0000]
            );

            // 3. Work Orders
            $wo1 = MrpWorkOrder::updateOrCreate(
                ['tenant_id' => $tenantId, 'wo_number' => 'WO-2026-0042'],
                [
                    'bom_id' => $bomSourdough->id,
                    'work_center_id' => $wcOven->id,
                    'product_name' => 'Artisan Sourdough Loaf 500g',
                    'product_sku' => 'BAK-SRD-500',
                    'bom_version' => 'v2.1',
                    'planned_qty' => 300.00,
                    'produced_qty' => 180.00,
                    'rejected_qty' => 3.00,
                    'unit' => 'Loaf',
                    'branch' => 'Central Bakery Hub',
                    'priority' => 'High',
                    'status' => 'In Progress',
                    'progress_percent' => 60,
                    'start_date' => now()->subHours(6),
                    'due_date' => now()->addHours(12),
                    'assigned_to' => 'Pierre Dupont',
                ]
            );

            $wo2 = MrpWorkOrder::updateOrCreate(
                ['tenant_id' => $tenantId, 'wo_number' => 'WO-2026-0043'],
                [
                    'bom_id' => $bomBurger->id,
                    'work_center_id' => $wcButchery->id,
                    'product_name' => 'Signature Beef Patty (150g x 10)',
                    'product_sku' => 'CK-PATTY-150',
                    'bom_version' => 'v1.4',
                    'planned_qty' => 500.00,
                    'produced_qty' => 500.00,
                    'rejected_qty' => 0.00,
                    'unit' => 'Pack',
                    'branch' => 'Central Kitchen HQ',
                    'priority' => 'Normal',
                    'status' => 'Completed',
                    'progress_percent' => 100,
                    'start_date' => now()->subDays(1),
                    'due_date' => now()->subHours(4),
                    'completed_at' => now()->subHours(4),
                    'assigned_to' => 'Chef Malik',
                ]
            );

            $wo3 = MrpWorkOrder::updateOrCreate(
                ['tenant_id' => $tenantId, 'wo_number' => 'WO-2026-0044'],
                [
                    'bom_id' => $bomIot->id,
                    'work_center_id' => $wcSmt->id,
                    'product_name' => 'Industrial IoT Gateway v3',
                    'product_sku' => 'ELC-IOT-GW3',
                    'bom_version' => 'v3.0',
                    'planned_qty' => 100.00,
                    'produced_qty' => 20.00,
                    'rejected_qty' => 1.00,
                    'unit' => 'Unit',
                    'branch' => 'Main Electronics Plant',
                    'priority' => 'Urgent',
                    'status' => 'In Progress',
                    'progress_percent' => 20,
                    'start_date' => now()->subHours(10),
                    'due_date' => now()->addDays(2),
                    'assigned_to' => 'Eng. Kevin Tan',
                ]
            );

            // 4. Quality Inspections
            MrpQualityInspection::updateOrCreate(
                ['tenant_id' => $tenantId, 'inspection_number' => 'QC-2026-0112'],
                [
                    'work_order_id' => $wo1->id,
                    'type' => 'in_process',
                    'wo_number' => 'WO-2026-0042',
                    'product_name' => 'Artisan Sourdough Loaf 500g',
                    'item_code' => 'BAK-SRD-500',
                    'lot_or_batch' => 'LOT-2026-0911A',
                    'checkpoint_name' => 'Internal Loaf Temperature Check',
                    'target' => '96°C - 98°C',
                    'actual' => '97.2°C',
                    'sample_size' => 5,
                    'inspected_qty' => 5,
                    'passed_qty' => 5,
                    'failed_qty' => 0,
                    'status' => 'passed',
                    'inspector' => 'Helena Vance',
                    'inspection_date' => now()->subHours(2),
                    'notes' => 'Complies with HACCP standard bake temperatures.',
                ]
            );

            MrpQualityInspection::updateOrCreate(
                ['tenant_id' => $tenantId, 'inspection_number' => 'QC-2026-0113'],
                [
                    'work_order_id' => $wo1->id,
                    'type' => 'final_finished',
                    'wo_number' => 'WO-2026-0042',
                    'product_name' => 'Artisan Sourdough Loaf 500g',
                    'item_code' => 'BAK-SRD-500',
                    'lot_or_batch' => 'LOT-2026-0911A',
                    'checkpoint_name' => 'Crust Color & Visual Aeration',
                    'target' => 'Deep amber blistered crust with open crumb',
                    'actual' => 'Compliant',
                    'sample_size' => 5,
                    'inspected_qty' => 5,
                    'passed_qty' => 5,
                    'failed_qty' => 0,
                    'status' => 'passed',
                    'inspector' => 'Helena Vance',
                    'inspection_date' => now()->subHour(),
                    'notes' => 'Color shade verified against standard culinary sample card.',
                ]
            );

            // 5. Material Issues
            MrpMaterialIssue::updateOrCreate(
                ['tenant_id' => $tenantId, 'issue_number' => 'ISS-2026-081'],
                [
                    'work_order_id' => $wo1->id,
                    'item_name' => 'Organic Bread Flour T65',
                    'item_sku' => 'RM-FLOUR-T65',
                    'quantity' => 150.00,
                    'unit' => 'kg',
                    'issued_to' => 'Bakery Production Line',
                    'status' => 'issued',
                    'issued_at' => now()->subHours(6),
                ]
            );

            // 6. Finished Goods Receipts
            MrpFinishedGood::updateOrCreate(
                ['tenant_id' => $tenantId, 'receipt_number' => 'FG-REC-2026-091'],
                [
                    'work_order_id' => $wo1->id,
                    'product_name' => 'Artisan Sourdough Loaf 500g',
                    'product_sku' => 'BAK-SRD-500',
                    'quantity' => 180.00,
                    'unit' => 'Loaf',
                    'lot_number' => 'LOT-2026-0911A',
                    'warehouse_location' => 'WH-BAK-A1',
                    'received_at' => now()->subHours(1),
                ]
            );
        }
    }
}
