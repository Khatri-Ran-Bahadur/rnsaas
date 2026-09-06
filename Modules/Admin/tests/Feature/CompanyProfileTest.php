<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

uses(RefreshDatabase::class);

function createCompanyProfileTenantAdmin(string $tenantName = 'Himalayan Tech Inc'): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'name' => $tenantName,
        'slug' => 'himalayan-tech',
        'status' => TenantStatus::Active,
        'country_code' => 'NP',
        'timezone' => 'Asia/Kathmandu',
        'currency' => 'NPR',
        'locale' => 'en',
    ]);

    $membership = TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    return [$user, $tenant, $membership];
}

it('allows organization admin to view company profile edit page', function (): void {
    [$user, $tenant] = createCompanyProfileTenantAdmin();

    $response = $this->actingAs($user)->get('/admin/company-profile');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/CompanyProfile/Edit')
            ->where('tenant.id', $tenant->id)
            ->where('tenant.name', 'Himalayan Tech Inc')
            ->where('tenant.slug', 'himalayan-tech')
            ->where('tenant.currency', 'NPR')
    );
});

it('allows organization admin to update company profile details', function (): void {
    [$user, $tenant] = createCompanyProfileTenantAdmin();

    $response = $this->actingAs($user)->put('/admin/company-profile', [
        'name' => 'Himalayan Innovations Pvt. Ltd.',
        'slug' => 'himalayan-innovations',
        'industry' => 'Technology & Software',
        'country_code' => 'np',
        'timezone' => 'Asia/Kathmandu',
        'currency' => 'npr',
        'locale' => 'en',
        'email' => 'hello@himalayan.com',
        'phone' => '+977 1 4412345',
        'website' => 'https://himalayan.com',
        'tax_id' => '601234567',
        'registration_number' => 'REG-987654',
        'address_line_1' => 'Putalisadak, Ward 28',
        'address_line_2' => 'Level 3, Star Building',
        'city' => 'Kathmandu',
        'state' => 'Bagmati',
        'postal_code' => '44600',
        'description' => 'Leading enterprise SaaS provider in the Himalayas.',
    ]);

    $response->assertRedirect('/admin/company-profile');

    $tenant->refresh();

    expect($tenant->name)->toBe('Himalayan Innovations Pvt. Ltd.');
    expect($tenant->slug)->toBe('himalayan-innovations');
    expect($tenant->industry)->toBe('Technology & Software');
    expect($tenant->country_code)->toBe('NP');
    expect($tenant->currency)->toBe('NPR');
    expect($tenant->settings['email'])->toBe('hello@himalayan.com');
    expect($tenant->settings['phone'])->toBe('+977 1 4412345');
    expect($tenant->settings['website'])->toBe('https://himalayan.com');
    expect($tenant->settings['tax_id'])->toBe('601234567');
    expect($tenant->settings['city'])->toBe('Kathmandu');
});

it('prevents taking a slug already used by another tenant', function (): void {
    [$user, $tenant] = createCompanyProfileTenantAdmin();

    Tenant::factory()->create([
        'slug' => 'already-taken',
    ]);

    $response = $this->actingAs($user)->put('/admin/company-profile', [
        'name' => 'New Name',
        'slug' => 'already-taken',
        'timezone' => 'Asia/Kathmandu',
        'currency' => 'NPR',
        'locale' => 'en',
    ]);

    $response->assertSessionHasErrors(['slug']);
});
