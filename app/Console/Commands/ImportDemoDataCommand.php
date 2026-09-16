<?php

namespace App\Console\Commands;

use Database\Seeders\SystemSetupSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Modules\Accounting\Database\Seeders\AccountingDatabaseSeeder;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Customer;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\SalesInvoice;
use Modules\Accounting\Models\Vendor;
use Modules\Admin\Database\Seeders\AdminDatabaseSeeder;
use Modules\HRM\Database\Seeders\HRMDatabaseSeeder;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\EmployeeDocument;
use Modules\HRM\Models\Holiday;
use Modules\HRM\Models\LeaveRequest;
use Modules\HRM\Models\Overtime;
use Modules\HRM\Models\Shift;
use Modules\HRM\Models\WorkSchedule;
use Modules\Inventory\Database\Seeders\InventoryDatabaseSeeder;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryLocation;
use Modules\Inventory\Models\InventoryStockAdjustment;
use Modules\Inventory\Models\InventoryUnit;
use Modules\MRP\Database\Seeders\MrpDatabaseSeeder;
use Modules\MRP\Models\MrpBom;
use Modules\MRP\Models\MrpFinishedGood;
use Modules\MRP\Models\MrpMaterialIssue;
use Modules\MRP\Models\MrpQualityInspection;
use Modules\MRP\Models\MrpWorkCenter;
use Modules\MRP\Models\MrpWorkOrder;
use Modules\Payroll\Database\Seeders\PayrollDatabaseSeeder;
use Modules\Payroll\Models\PayrollGroup;
use Modules\Payroll\Models\PayrollRun;
use Modules\POS\Database\Seeders\PosDatabaseSeeder;
use Modules\POS\Models\PosKitchenTicket;
use Modules\POS\Models\PosOrder;
use Modules\POS\Models\PosRegister;
use Modules\POS\Models\PosShift;
use Modules\POS\Models\PosTable;
use Modules\Subscription\Database\Seeders\SubscriptionDatabaseSeeder;
use Modules\Subscription\Models\Coupon;
use Modules\Subscription\Models\Feature;
use Modules\Subscription\Models\Plan;
use Modules\SuperAdmin\Database\Seeders\CustomPageSeeder;
use Modules\SuperAdmin\Database\Seeders\NotificationTemplateSeeder;
use Modules\SuperAdmin\Database\Seeders\PlatformSettingsSeeder;
use Modules\SuperAdmin\Database\Seeders\SuperAdminDatabaseSeeder;
use Modules\SuperAdmin\Models\CustomPage;
use Modules\SuperAdmin\Models\NotificationTemplate;
use Modules\Tax\Database\Seeders\TaxDatabaseSeeder;
use Modules\Tax\Models\TaxCategory;
use Modules\Tax\Models\TaxExemption;
use Modules\Tax\Models\TaxRate;
use Modules\Tax\Models\TaxRule;
use Modules\Tax\Models\TaxType;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\Designation;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantStaff;

#[Signature('demo:import {--tenant= : The tenant ID or slug to import demo data into} {--fresh : Refresh database migrations prior to seeding}')]
#[Description('Import full production demo datasets across all platform modules: Tenancy, Subscriptions, Tax, Accounting, Inventory, POS, MRP, HRM, and Payroll')]
class ImportDemoDataCommand extends Command
{
    protected $signature = 'demo:import {--tenant= : The tenant ID or slug to import demo data into} {--fresh : Refresh database migrations prior to seeding}';

    protected $description = 'Import full production demo datasets across all platform modules: Tenancy, Subscriptions, Tax, Accounting, Inventory, POS, MRP, HRM, and Payroll';

    public function handle(): int
    {
        $this->info('Starting SathiSaaS Enterprise Demo Data Importer...');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->components->task('Refreshing database schema (migrate:fresh)', function () {
                $this->callSilent('migrate:fresh', ['--force' => true]);

                return true;
            });
        }

        // Resolve Target Tenant (if passed)
        $tenantInput = $this->option('tenant');
        $targetTenantId = null;

        if ($tenantInput) {
            $tenant = is_numeric($tenantInput)
                ? Tenant::find($tenantInput)
                : Tenant::where('slug', $tenantInput)->first();

            if (! $tenant) {
                $this->error("Tenant '{$tenantInput}' does not exist in the database.");

                return self::FAILURE;
            }

            $targetTenantId = $tenant->id;
            $this->components->info("Targeting Tenant: [{$tenant->id}] {$tenant->name} ({$tenant->slug})");
        } else {
            // If no tenant exists, establish the baseline system and root organization
            if (Tenant::count() === 0) {
                $this->components->task('Initializing Baseline Root Tenant & Platform Infrastructure', function () {
                    $this->callSilent('db:seed', ['--class' => SystemSetupSeeder::class, '--force' => true]);

                    return true;
                });
            }
        }

        // 1. SuperAdmin & Core Platform
        $this->components->task('Seeding SuperAdmin & Platform (Roles, Permissions, Settings, Pages, Templates)', function () {
            $this->callSilent('db:seed', ['--class' => SuperAdminDatabaseSeeder::class, '--force' => true]);
            $this->callSilent('db:seed', ['--class' => PlatformSettingsSeeder::class, '--force' => true]);
            $this->callSilent('db:seed', ['--class' => CustomPageSeeder::class, '--force' => true]);
            $this->callSilent('db:seed', ['--class' => NotificationTemplateSeeder::class, '--force' => true]);

            return true;
        });

        // 2. Subscription Plans & Features
        $this->components->task('Seeding Subscription Engine (Features, Tiered Plans, Promo Coupons)', function () {
            $this->callSilent('db:seed', ['--class' => SubscriptionDatabaseSeeder::class, '--force' => true]);

            return true;
        });

        // 3. Tenancy & Administration (Branches, Departments, Designations, Staff)
        $this->components->task('Seeding Organization Structure (Branches, Departments, Designations, Staff Members)', function () use ($targetTenantId) {
            resolve(AdminDatabaseSeeder::class)->run($targetTenantId);

            return true;
        });

        // 4. Statutory Tax System
        $this->components->task('Seeding Statutory Tax System (Tax Types, Categories, Rates, Rules, Exemptions)', function () use ($targetTenantId) {
            resolve(TaxDatabaseSeeder::class)->run($targetTenantId);

            return true;
        });

        // 5. Financial Accounting
        $this->components->task('Seeding Accounting System (Chart of Accounts, Customers, Vendors, Invoices, Bills, Journal Entries)', function () use ($targetTenantId) {
            resolve(AccountingDatabaseSeeder::class)->run($targetTenantId);

            return true;
        });

        // 6. Multi-Warehouse Inventory
        $this->components->task('Seeding Inventory System (Categories, Units, Warehouses, Items/Products, Stock Adjustments)', function () use ($targetTenantId) {
            resolve(InventoryDatabaseSeeder::class)->run($targetTenantId);

            return true;
        });

        // 7. POS & Restaurant Counter
        $this->components->task('Seeding POS & Restaurant Engine (Registers, Shifts, Tables, Orders, Kitchen Tickets)', function () use ($targetTenantId) {
            resolve(PosDatabaseSeeder::class)->run($targetTenantId);

            return true;
        });

        // 8. MRP Manufacturing & Production
        $this->components->task('Seeding MRP Manufacturing (Work Centers, BOM Recipes, Work Orders, QA Inspections, Finished Goods)', function () use ($targetTenantId) {
            resolve(MrpDatabaseSeeder::class)->run($targetTenantId);

            return true;
        });

        // 9. HRM System
        $this->components->task('Seeding HRM & Workforce System (Shifts, Work Schedules, Working Days, Holidays, Attendance, Leaves, Overtime, Documents)', function () use ($targetTenantId) {
            resolve(HRMDatabaseSeeder::class)->run($targetTenantId);

            return true;
        });

        // 10. Payroll & Compensation
        $this->components->task('Seeding Payroll & Compensation (Payroll Groups, Monthly Runs, Salary Calculations)', function () use ($targetTenantId) {
            resolve(PayrollDatabaseSeeder::class)->run($targetTenantId);

            return true;
        });

        $this->newLine();
        $this->info('Comprehensive Demo Data Successfully Imported Across All Platform Modules!');
        $this->newLine();

        // Print Summary Table
        $this->table(
            ['Module Area', 'Entity / Dataset', 'Active Database Records'],
            [
                ['Platform & SuperAdmin', 'Custom CMS Pages', CustomPage::count()],
                ['Platform & SuperAdmin', 'Notification Templates', NotificationTemplate::count()],
                ['Subscriptions', 'Platform Plans', Plan::count()],
                ['Subscriptions', 'Plan Features', Feature::count()],
                ['Subscriptions', 'Coupons & Discounts', Coupon::count()],
                ['Tenancy & Org', 'Active Organizations / Tenants', Tenant::count()],
                ['Tenancy & Org', 'Branches & Outlets', Branch::count()],
                ['Tenancy & Org', 'Corporate Departments', Department::count()],
                ['Tenancy & Org', 'Job Designations', Designation::count()],
                ['Tenancy & Org', 'Staff Members', TenantStaff::count()],
                ['Statutory Tax', 'Tax Types', TaxType::count()],
                ['Statutory Tax', 'Tax Categories', TaxCategory::count()],
                ['Statutory Tax', 'Tax Rates', TaxRate::count()],
                ['Statutory Tax', 'Tax Rules', TaxRule::count()],
                ['Statutory Tax', 'Tax Exemptions', TaxExemption::count()],
                ['Financial Accounting', 'Chart of Accounts', Account::count()],
                ['Financial Accounting', 'Customers', Customer::count()],
                ['Financial Accounting', 'Vendors', Vendor::count()],
                ['Financial Accounting', 'Sales Invoices', SalesInvoice::count()],
                ['Financial Accounting', 'Purchase Bills', PurchaseBill::count()],
                ['Financial Accounting', 'Journal Entries', JournalEntry::count()],
                ['Inventory & Warehousing', 'Categories', InventoryCategory::count()],
                ['Inventory & Warehousing', 'Units of Measure', InventoryUnit::count()],
                ['Inventory & Warehousing', 'Warehouses & Locations', InventoryLocation::count()],
                ['Inventory & Warehousing', 'Items & Stock Products', InventoryItem::count()],
                ['Inventory & Warehousing', 'Stock Adjustments', InventoryStockAdjustment::count()],
                ['POS & Restaurant', 'POS Registers', PosRegister::count()],
                ['POS & Restaurant', 'Cash Shifts', PosShift::count()],
                ['POS & Restaurant', 'Dining Tables', PosTable::count()],
                ['POS & Restaurant', 'Completed Orders', PosOrder::count()],
                ['POS & Restaurant', 'Kitchen Display Tickets', PosKitchenTicket::count()],
                ['MRP Manufacturing', 'Work Centers', MrpWorkCenter::count()],
                ['MRP Manufacturing', 'Bill of Materials (BOM)', MrpBom::count()],
                ['MRP Manufacturing', 'Work Orders', MrpWorkOrder::count()],
                ['MRP Manufacturing', 'Quality Inspections', MrpQualityInspection::count()],
                ['MRP Manufacturing', 'Material Issues', MrpMaterialIssue::count()],
                ['MRP Manufacturing', 'Finished Goods Batches', MrpFinishedGood::count()],
                ['HRM System', 'Working Shifts', Shift::count()],
                ['HRM System', 'Work Schedules', WorkSchedule::count()],
                ['HRM System', 'Company & Public Holidays', Holiday::count()],
                ['HRM System', 'Attendance Logs', Attendance::count()],
                ['HRM System', 'Leave Requests', LeaveRequest::count()],
                ['HRM System', 'Overtime Records', Overtime::count()],
                ['HRM System', 'Employee Documents', EmployeeDocument::count()],
                ['Payroll & Compensation', 'Payroll Groups', PayrollGroup::count()],
                ['Payroll & Compensation', 'Payroll Runs', PayrollRun::count()],
            ]
        );

        return self::SUCCESS;
    }
}
