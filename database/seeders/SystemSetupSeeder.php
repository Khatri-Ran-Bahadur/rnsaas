<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Accounting\Domain\Enums\AccountClassification;
use Modules\Accounting\Domain\Enums\AccountNormalBalance;
use Modules\Accounting\Domain\Enums\FinancialStatement;
use Modules\Accounting\Domain\Enums\FinancialStatementSection;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AccountGroup;
use Modules\Accounting\Models\AccountType;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryLocation;
use Modules\Inventory\Models\InventoryUnit;
use Modules\MRP\Models\MrpSetting;
use Modules\MRP\Models\MrpWorkCenter;
use Modules\Payroll\Models\PayrollGroup;
use Modules\POS\Models\PosKitchenStation;
use Modules\POS\Models\PosRegister;
use Modules\Subscription\Database\Seeders\SubscriptionFeatureSeeder;
use Modules\Subscription\Database\Seeders\SubscriptionPlanSeeder;
use Modules\SuperAdmin\Database\Seeders\PlatformSettingsSeeder;
use Modules\SuperAdmin\Database\Seeders\SuperAdminDatabaseSeeder;
use Modules\Tax\Database\Seeders\TaxDatabaseSeeder;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;

class SystemSetupSeeder extends Seeder
{
    /**
     * Run clean core system setup without mock transactions or fake orders.
     */
    public function run(): void
    {
        // 1. Roles, Permissions & Subscription Core
        $this->call([
            SuperAdminDatabaseSeeder::class,
            PlatformSettingsSeeder::class,
            SubscriptionFeatureSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        // 2. Default Root Tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'main-company'],
            [
                'public_id' => (string) Str::ulid(),
                'name' => 'Main Organization HQ',
                'status' => TenantStatus::Active,
                'country_code' => 'MY',
                'currency' => 'MYR',
                'timezone' => 'Asia/Kuala_Lumpur',
            ]
        );
        $tenantId = $tenant->id;

        // 3. Accounting Foundation (Types, Groups & Core Chart of Accounts)
        $assetType = AccountType::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'CURR_ASSET'],
            [
                'name' => 'Current Assets',
                'classification' => AccountClassification::ASSET->value,
                'normal_balance' => AccountNormalBalance::DEBIT->value,
                'financial_statement' => FinancialStatement::BALANCE_SHEET->value,
                'financial_statement_section' => FinancialStatementSection::ASSETS->value,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $liabilityType = AccountType::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'CURR_LIAB'],
            [
                'name' => 'Current Liabilities',
                'classification' => AccountClassification::LIABILITY->value,
                'normal_balance' => AccountNormalBalance::CREDIT->value,
                'financial_statement' => FinancialStatement::BALANCE_SHEET->value,
                'financial_statement_section' => FinancialStatementSection::LIABILITIES->value,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $revenueType = AccountType::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'OP_REV'],
            [
                'name' => 'Operating Revenue',
                'classification' => AccountClassification::REVENUE->value,
                'normal_balance' => AccountNormalBalance::CREDIT->value,
                'financial_statement' => FinancialStatement::PROFIT_AND_LOSS->value,
                'financial_statement_section' => FinancialStatementSection::REVENUE->value,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $expenseType = AccountType::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'OP_EXP'],
            [
                'name' => 'Operating Expenses',
                'classification' => AccountClassification::EXPENSE->value,
                'normal_balance' => AccountNormalBalance::DEBIT->value,
                'financial_statement' => FinancialStatement::PROFIT_AND_LOSS->value,
                'financial_statement_section' => FinancialStatementSection::OPERATING_EXPENSES->value,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $assetGroup = AccountGroup::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'GRP_CA'],
            [
                'account_type_id' => $assetType->id,
                'name' => 'Current Assets Group',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $liabilityGroup = AccountGroup::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'GRP_CL'],
            [
                'account_type_id' => $liabilityType->id,
                'name' => 'Current Liabilities Group',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $revenueGroup = AccountGroup::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'GRP_REV'],
            [
                'account_type_id' => $revenueType->id,
                'name' => 'Revenue Group',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $expenseGroup = AccountGroup::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'GRP_EXP'],
            [
                'account_type_id' => $expenseType->id,
                'name' => 'Expense Group',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        Account::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => '1000'],
            [
                'account_type_id' => $assetType->id,
                'account_group_id' => $assetGroup->id,
                'name' => 'Cash in Hand',
                'financial_statement_section' => FinancialStatementSection::ASSETS->value,
                'is_postable' => true,
                'is_control_account' => false,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        Account::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => '1010'],
            [
                'account_type_id' => $assetType->id,
                'account_group_id' => $assetGroup->id,
                'name' => 'Bank Operating Account',
                'financial_statement_section' => FinancialStatementSection::ASSETS->value,
                'is_postable' => true,
                'is_control_account' => false,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        Account::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => '1200'],
            [
                'account_type_id' => $assetType->id,
                'account_group_id' => $assetGroup->id,
                'name' => 'Accounts Receivable',
                'financial_statement_section' => FinancialStatementSection::ASSETS->value,
                'is_postable' => true,
                'is_control_account' => true,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        Account::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => '2000'],
            [
                'account_type_id' => $liabilityType->id,
                'account_group_id' => $liabilityGroup->id,
                'name' => 'Accounts Payable',
                'financial_statement_section' => FinancialStatementSection::LIABILITIES->value,
                'is_postable' => true,
                'is_control_account' => true,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        Account::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => '4000'],
            [
                'account_type_id' => $revenueType->id,
                'account_group_id' => $revenueGroup->id,
                'name' => 'Sales Revenue',
                'financial_statement_section' => FinancialStatementSection::REVENUE->value,
                'is_postable' => true,
                'is_control_account' => false,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        Account::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => '5000'],
            [
                'account_type_id' => $expenseType->id,
                'account_group_id' => $expenseGroup->id,
                'name' => 'Cost of Goods Sold',
                'financial_statement_section' => FinancialStatementSection::OPERATING_EXPENSES->value,
                'is_postable' => true,
                'is_control_account' => false,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        // 4. Statutory Tax Core
        $this->call(TaxDatabaseSeeder::class);

        // 5. Inventory Core (Units, Locations, Categories)
        $units = [
            ['code' => 'pcs', 'name' => 'Pieces', 'symbol' => 'pcs', 'is_base' => true],
            ['code' => 'box', 'name' => 'Box', 'symbol' => 'box', 'is_base' => false],
            ['code' => 'ctn', 'name' => 'Carton', 'symbol' => 'ctn', 'is_base' => false],
            ['code' => 'kg', 'name' => 'Kilogram', 'symbol' => 'kg', 'is_base' => true],
            ['code' => 'g', 'name' => 'Gram', 'symbol' => 'g', 'is_base' => false],
            ['code' => 'l', 'name' => 'Litre', 'symbol' => 'L', 'is_base' => true],
            ['code' => 'can', 'name' => 'Can', 'symbol' => 'can', 'is_base' => true],
        ];
        foreach ($units as $u) {
            InventoryUnit::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $u['code']],
                ['name' => $u['name'], 'symbol' => $u['symbol'], 'is_base' => $u['is_base'], 'status' => 'active']
            );
        }

        InventoryLocation::updateOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'WH-MAIN'],
            [
                'name' => 'HQ - Main Warehouse',
                'type' => 'warehouse',
                'address' => 'Central Logistics Hub',
                'status' => 'active',
            ]
        );

        InventoryCategory::updateOrCreate(
            ['tenant_id' => $tenantId, 'slug' => 'general-goods'],
            [
                'name' => 'General Goods',
                'code' => 'CAT-GEN',
                'status' => 'active',
                'description' => 'General products and commodities',
            ]
        );

        // 6. POS Core (Registers & Kitchen Stations)
        PosRegister::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'REG-01'],
            [
                'name' => 'Counter 01 - Main POS',
                'location' => 'Main Front Counter',
                'is_active' => true,
            ]
        );

        $stations = [
            ['code' => 'kitchen', 'name' => 'Main Hot Kitchen', 'icon' => 'Flame', 'display_order' => 1],
            ['code' => 'bar', 'name' => 'Bar & Beverage Station', 'icon' => 'Coffee', 'display_order' => 2],
            ['code' => 'grill', 'name' => 'Grill & Fryer Station', 'icon' => 'Utensils', 'display_order' => 3],
            ['code' => 'bakery', 'name' => 'Bakery & Pastry Station', 'icon' => 'CookingPot', 'display_order' => 4],
        ];
        foreach ($stations as $s) {
            PosKitchenStation::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => $s['code']],
                ['name' => $s['name'], 'icon' => $s['icon'], 'is_active' => true, 'display_order' => $s['display_order']]
            );
        }

        // 7. MRP Core Settings & Work Centers
        MrpSetting::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'auto_reserve_materials' => true,
                'allow_negative_raw_materials' => false,
                'require_qa_approval_before_fg' => true,
                'default_scrap_percentage' => 2.00,
                'mrp_planning_horizon_days' => 30,
                'default_overhead_allocation_rate' => 15.00,
                'track_lot_genealogy' => true,
                'require_manager_override_for_scrap' => true,
            ]
        );

        MrpWorkCenter::updateOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'WC-MAIN-LINE'],
            [
                'name' => 'Primary Production Line',
                'branch' => 'Main Manufacturing Plant',
                'type' => 'machine',
                'capacity_hours_per_day' => 16.00,
                'hourly_rate' => 40.00,
                'hourly_cost' => 40.00,
                'efficiency_percentage' => 90,
                'oee_percentage' => 85,
                'utilization_percent' => 80,
                'status' => 'operational',
                'machines_count' => 1,
            ]
        );

        // 8. Payroll Core Group
        PayrollGroup::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'PG-HQ-M'],
            [
                'name' => 'HQ & Corporate Monthly',
                'cycle' => 'monthly',
                'pay_day' => 28,
                'is_active' => true,
            ]
        );
    }
}
