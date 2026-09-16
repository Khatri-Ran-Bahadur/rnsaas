<?php

use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Customer;
use Modules\Accounting\Models\SalesInvoice;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\Holiday;
use Modules\HRM\Models\Shift;
use Modules\HRM\Models\WorkSchedule;
use Modules\Inventory\Models\InventoryItem;
use Modules\MRP\Models\MrpBom;
use Modules\Payroll\Models\PayrollGroup;
use Modules\POS\Models\PosOrder;
use Modules\Subscription\Models\Coupon;
use Modules\Subscription\Models\Plan;
use Modules\SuperAdmin\Models\CustomPage;
use Modules\Tax\Models\TaxType;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\Designation;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantStaff;

test('demo:import command imports datasets across all platform modules', function () {
    $this->artisan('demo:import')
        ->assertSuccessful();

    // Verify SuperAdmin & Platform
    expect(CustomPage::count())->toBeGreaterThan(0);

    // Verify Subscriptions
    expect(Plan::count())->toBeGreaterThan(0);
    expect(Coupon::count())->toBeGreaterThan(0);

    // Verify Tenancy & Admin Organization Structure
    expect(Tenant::count())->toBeGreaterThan(0);
    expect(Branch::count())->toBeGreaterThan(0);
    expect(Department::count())->toBeGreaterThan(0);
    expect(Designation::count())->toBeGreaterThan(0);
    expect(TenantStaff::count())->toBeGreaterThan(0);

    // Verify Statutory Tax
    expect(TaxType::count())->toBeGreaterThan(0);

    // Verify Financial Accounting
    expect(Account::count())->toBeGreaterThan(0);
    expect(Customer::count())->toBeGreaterThan(0);
    expect(SalesInvoice::count())->toBeGreaterThan(0);

    // Verify Inventory
    expect(InventoryItem::count())->toBeGreaterThan(0);

    // Verify POS
    expect(PosOrder::count())->toBeGreaterThan(0);

    // Verify MRP
    expect(MrpBom::count())->toBeGreaterThan(0);

    // Verify HRM
    expect(Shift::count())->toBeGreaterThan(0);
    expect(WorkSchedule::count())->toBeGreaterThan(0);
    expect(Holiday::count())->toBeGreaterThan(0);
    expect(Attendance::count())->toBeGreaterThan(0);

    // Verify Payroll
    expect(PayrollGroup::count())->toBeGreaterThan(0);
});

test('demo:import accepts specific tenant option', function () {
    $tenant = Tenant::firstOrCreate(
        ['slug' => 'test-import-tenant'],
        ['name' => 'Targeted Tenant Org']
    );

    $this->artisan('demo:import', ['--tenant' => $tenant->slug])
        ->assertSuccessful();

    expect(Branch::where('tenant_id', $tenant->id)->count())->toBeGreaterThan(0);
    expect(Department::where('tenant_id', $tenant->id)->count())->toBeGreaterThan(0);
    expect(Shift::where('tenant_id', $tenant->id)->count())->toBeGreaterThan(0);
});

test('demo:import fails gracefully when tenant not found', function () {
    $this->artisan('demo:import', ['--tenant' => 'non-existent-tenant-xyz'])
        ->assertFailed();
});
