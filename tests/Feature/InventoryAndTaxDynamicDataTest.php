<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Accounting\Models\SalesInvoice;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryLocation;
use Modules\Inventory\Models\InventoryUnit;
use Modules\Tax\Models\TaxCategory;
use Modules\Tax\Models\TaxExemption;
use Modules\Tax\Models\TaxRate;
use Modules\Tax\Models\TaxRule;
use Modules\Tax\Models\TaxSetting;
use Modules\Tax\Models\TaxType;
use Modules\Tenancy\Models\Tenant;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create();
    $this->user->tenants()->attach($this->tenant->id, ['status' => 'active']);

    $this->artisan('demo:import')->assertSuccessful();
});

test('demo:import command populates all modules with live production database data', function (): void {
    expect(TaxType::count())->toBeGreaterThan(0)
        ->and(TaxCategory::count())->toBeGreaterThan(0)
        ->and(TaxRate::count())->toBeGreaterThan(0)
        ->and(TaxRule::count())->toBeGreaterThan(0)
        ->and(TaxExemption::count())->toBeGreaterThan(0)
        ->and(InventoryCategory::count())->toBeGreaterThan(0)
        ->and(InventoryUnit::count())->toBeGreaterThan(0)
        ->and(InventoryLocation::count())->toBeGreaterThan(0)
        ->and(InventoryItem::count())->toBeGreaterThan(0)
        ->and(SalesInvoice::count())->toBeGreaterThan(0);
});

test('Inventory endpoints return dynamic database records via Inertia', function (): void {
    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/inventory/categories')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Categories/Index')
            ->has('categories')
            ->has('parentCategories')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/inventory/items')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Items/Index')
            ->has('items.data')
            ->has('stats')
            ->has('categories')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/inventory/locations')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Locations/Index')
            ->has('locations')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/inventory/units')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Units/Index')
            ->has('units')
        );
});

test('Tax endpoints return dynamic database records via Inertia', function (): void {
    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/tax/rates')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tax/Rates/Index')
            ->has('taxRates')
            ->has('taxTypes')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/tax/rules')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tax/Rules/Index')
            ->has('rules')
            ->has('taxRates')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/tax/types')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tax/Types/Index')
            ->has('taxTypes')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/tax/categories')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tax/Categories/Index')
            ->has('categories')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/tax/exemptions')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tax/Exemptions/Index')
            ->has('exemptions')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/tax/settings')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tax/Settings/Index')
            ->has('settings')
            ->has('availableRates')
            ->has('settings.country')
        );

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/tax/accounts')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tax/Accounts/Index')
            ->has('settings')
            ->has('accounts')
            ->has('taxRates')
            ->has('taxTypes')
        );
});

test('Tax settings can be dynamically updated and persisted in database', function (): void {
    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->put('/admin/tax/settings', [
            'country' => 'Malaysia',
            'tax_registration_number' => 'W10-9999-88887777',
            'registered_business_name' => 'Ran SaaS Global Enterprise',
            'tax_authority_name' => 'LHDN Malaysia',
            'tax_regime' => 'sst',
            'reporting_frequency' => 'monthly',
            'accounting_method' => 'cash',
            'default_pricing_mode' => 'inclusive',
            'allow_cashier_tax_override' => true,
            'rounding_level' => 'line',
            'rounding_precision' => 2,
            'rounding_direction' => 'half_up',
            'display_tax_summary_on_invoices' => true,
            'enable_einvoice_compliance' => true,
        ])
        ->assertRedirect();

    $setting = TaxSetting::where('tenant_id', $this->tenant->id)->first();
    expect($setting)->not->toBeNull()
        ->and($setting->tax_registration_number)->toBe('W10-9999-88887777')
        ->and($setting->registered_business_name)->toBe('Ran SaaS Global Enterprise')
        ->and($setting->allow_cashier_tax_override)->toBeTrue();
});

test('Tax preset for Nepal provisions statutory 13% VAT and TDS rates dynamically', function (): void {
    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->post('/admin/tax/settings/apply-preset', [
            'country' => 'NP',
        ])
        ->assertRedirect();

    $setting = TaxSetting::where('tenant_id', $this->tenant->id)->first();
    expect($setting)->not->toBeNull()
        ->and($setting->country)->toBe('NP')
        ->and($setting->tax_regime)->toBe('vat')
        ->and($setting->tax_authority_name)->toContain('Inland Revenue Department');

    $vat13 = TaxRate::where('tenant_id', $this->tenant->id)->where('code', 'VAT-13')->first();
    expect($vat13)->not->toBeNull()
        ->and((float) $vat13->rate)->toBe(13.0)
        ->and($vat13->timeline_status)->toBe('active');

    $exempt = TaxRate::where('tenant_id', $this->tenant->id)->where('code', 'VAT-EXEMPT')->first();
    expect($exempt)->not->toBeNull()
        ->and((float) $exempt->rate)->toBe(0.0);
});

test('POS Register loads live inventory items and categories from database', function (): void {
    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/pos')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('POS/Register')
            ->has('categories')
            ->has('products')
            ->has('currentShift')
        );
});
