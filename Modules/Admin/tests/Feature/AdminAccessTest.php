<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\SalesInvoice;
use Modules\Inventory\Models\InventoryItem;
use Modules\POS\Models\PosOrder;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

it('redirects unauthenticated users from admin dashboard to login', function (): void {
    $response = $this->get('/admin/dashboard');

    $response->assertRedirect('/login');
});

it('redirects unauthenticated users from admin root to login', function (): void {
    $response = $this->get('/admin');

    $response->assertRedirect('/login');
});

it('redirects authenticated users with active membership from admin root to admin dashboard', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'status' => TenantStatus::Active,
    ]);

    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $response = $this->actingAs($user)->get('/admin');

    $response->assertRedirect(route('admin.dashboard'));
});

it('renders the organization login page for unauthenticated visitors', function (): void {
    $response = $this->get('/admin/login');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page->component('Admin/Auth/Login')
    );
});

it('redirects authenticated superadmin from admin login to superadmin dashboard', function (): void {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('SuperAdmin');

    $response = $this->actingAs($superadmin)->get('/admin/login');

    $response->assertRedirect(route('superadmin.dashboard'));
});

it('denies access to admin dashboard for user without an active organization membership', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $response->assertForbidden();
});

it('allows user with active membership to access admin dashboard', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'name' => 'Acme Corp',
        'status' => TenantStatus::Active,
    ]);

    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('tenant.name', 'Acme Corp')
            ->has('members')
            ->has('subscription')
            ->has('accounting')
            ->has('pos')
            ->has('inventory')
            ->has('operations')
            ->has('organizations', 1)
    );
});

it('scopes organization members index to current active tenant', function (): void {
    $user = User::factory()->create(['name' => 'Main Admin']);
    $tenantA = Tenant::factory()->create(['status' => TenantStatus::Active]);
    $tenantB = Tenant::factory()->create(['status' => TenantStatus::Active]);

    // Member of Tenant A
    TenantMembership::factory()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $otherMemberA = User::factory()->create(['name' => 'Team A Member']);
    TenantMembership::factory()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $otherMemberA->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    // Member of Tenant B only
    $memberB = User::factory()->create(['name' => 'Team B Member']);
    TenantMembership::factory()->create([
        'tenant_id' => $tenantB->id,
        'user_id' => $memberB->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $response = $this->actingAs($user)->get('/admin/members');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Members/Index')
            ->has('members.data', 2)
            ->where('members.data.0.name', fn ($name) => in_array($name, ['Main Admin', 'Team A Member']))
    );
});

it('allows switching organization if user belongs to multiple active organizations', function (): void {
    $user = User::factory()->create();
    $tenantA = Tenant::factory()->create(['name' => 'Org A', 'status' => TenantStatus::Active]);
    $tenantB = Tenant::factory()->create(['name' => 'Org B', 'status' => TenantStatus::Active]);

    TenantMembership::factory()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);
    TenantMembership::factory()->create([
        'tenant_id' => $tenantB->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->post(route('admin.tenant.switch', $tenantB));

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertEquals($tenantB->id, session('current_tenant_id'));
});

it('allows a superadmin to impersonate an active organization and records audit log', function (): void {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('SuperAdmin');

    $tenant = Tenant::factory()->create([
        'name' => 'Target Tenant',
        'status' => TenantStatus::Active,
    ]);

    $response = $this->actingAs($superadmin)
        ->post(route('superadmin.tenants.impersonate', $tenant));

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertEquals($tenant->id, session('impersonated_tenant_id'));
    $this->assertEquals($superadmin->id, session('impersonated_by_user_id'));

    // Check audit log
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'superadmin.tenant.impersonate',
        'tenant_id' => $tenant->id,
        'actor_id' => $superadmin->id,
    ]);

    // Now superadmin can access /admin/dashboard without having direct membership
    $dashboardResponse = $this->actingAs($superadmin)->get('/admin/dashboard');
    $dashboardResponse->assertSuccessful();
    $dashboardResponse->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('tenant.name', 'Target Tenant')
    );
});

it('prevents impersonating an inactive organization', function (): void {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('SuperAdmin');

    $tenant = Tenant::factory()->create([
        'status' => TenantStatus::Suspended,
    ]);

    $response = $this->actingAs($superadmin)
        ->post(route('superadmin.tenants.impersonate', $tenant));

    $response->assertForbidden();
});

it('allows exiting impersonation mode and records audit log', function (): void {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('SuperAdmin');

    $tenant = Tenant::factory()->create([
        'status' => TenantStatus::Active,
    ]);

    $response = $this->actingAs($superadmin)
        ->withSession([
            'impersonated_tenant_id' => $tenant->id,
            'impersonated_by_user_id' => $superadmin->id,
            'current_tenant_id' => $tenant->id,
        ])
        ->post(route('admin.impersonate.exit'));

    $response->assertRedirect(route('superadmin.tenancy.show', $tenant));
    $this->assertNull(session('impersonated_tenant_id'));

    // Check exit audit log
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'superadmin.tenant.impersonate_exit',
        'tenant_id' => $tenant->id,
        'actor_id' => $superadmin->id,
    ]);
});

it('correctly aggregates financial, pos, and inventory metrics for the executive dashboard', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'name' => 'Nexus Retail Corp',
        'status' => TenantStatus::Active,
    ]);

    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    if (class_exists(SalesInvoice::class)) {
        SalesInvoice::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'posted',
            'grand_total' => 1500.00,
        ]);
        SalesInvoice::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'issued',
            'grand_total' => 500.00,
        ]);
    }

    if (class_exists(PurchaseBill::class)) {
        PurchaseBill::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'issued',
            'grand_total' => 400.00,
        ]);
    }

    if (class_exists(PosOrder::class)) {
        PosOrder::create([
            'tenant_id' => $tenant->id,
            'order_number' => 'POS-TEST-100',
            'customer_name' => 'Jane Customer',
            'payment_method' => 'cash',
            'status' => 'completed',
            'grand_total' => 120.00,
        ]);
    }

    if (class_exists(InventoryItem::class)) {
        InventoryItem::create([
            'tenant_id' => $tenant->id,
            'name' => 'Low Stock Gadget',
            'sku' => 'SKU-LOW-1',
            'on_hand_stock' => 2,
            'reorder_point' => 10,
            'cost_price' => 25.00,
            'selling_price' => 50.00,
            'is_active' => true,
        ]);
        InventoryItem::create([
            'tenant_id' => $tenant->id,
            'name' => 'Empty Box',
            'sku' => 'SKU-OUT-1',
            'on_hand_stock' => 0,
            'reorder_point' => 5,
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);
    }

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('tenant.name', 'Nexus Retail Corp')
            ->where('accounting.total_income', 2000)
            ->where('accounting.total_expenses', 400)
            ->where('accounting.net_profit', 1600)
            ->where('accounting.receivables_total', 500)
            ->where('accounting.payables_total', 400)
            ->where('pos.today_sales', 120)
            ->where('pos.today_orders_count', 1)
            ->where('inventory.out_of_stock_count', 1)
            ->where('inventory.low_stock_count', 1)
    );
});
it('ensures sales invoices and invoice creation routes are accessible without 404', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'status' => TenantStatus::Active,
    ]);

    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    // Active subscription so tenant middleware passes
    $plan = Plan::factory()->create();
    TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    // 1. Canonical routes
    $this->actingAs($user)->get('/admin/accounting/invoices')->assertSuccessful();
    $this->actingAs($user)->get('/admin/accounting/invoices/create')->assertSuccessful();

    // 2. Sales invoice alias routes
    $this->actingAs($user)->get('/admin/accounting/sales-invoices')->assertSuccessful();
    $this->actingAs($user)->get('/admin/accounting/sales-invoices/create')->assertSuccessful();

    // 3. Short aliases
    $this->actingAs($user)->get('/admin/sales-invoices')->assertRedirect('/admin/accounting/invoices');
    $this->actingAs($user)->get('/admin/sales-invoices/create')->assertRedirect('/admin/accounting/invoices/create');
    $this->actingAs($user)->get('/admin/invoices')->assertRedirect('/admin/accounting/invoices');
    $this->actingAs($user)->get('/admin/invoices/create')->assertRedirect('/admin/accounting/invoices/create');
});
