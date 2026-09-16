<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Subscription\Models\Coupon;
use Modules\SuperAdmin\Models\NotificationTemplate;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

test('superadmin can view and toggle notification templates', function () {
    $admin = User::factory()->create();
    $admin->assignRole('SuperAdmin');

    $template = NotificationTemplate::create([
        'name' => 'Test Notification',
        'slug' => 'test-notification',
        'type' => 'system',
        'subject' => 'Hello {user_name}',
        'content' => '<p>Welcome {user_name} to {app_name}</p>',
        'variables' => ['user_name', 'app_name'],
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->get('/superadmin/notification-templates');
    $response->assertOk();

    $toggleResponse = $this->actingAs($admin)->post("/superadmin/notification-templates/{$template->id}/toggle");
    $toggleResponse->assertRedirect();

    expect($template->fresh()->is_active)->toBeFalse();
});

test('superadmin can create custom page and public user can view it', function () {
    $admin = User::factory()->create();
    $admin->assignRole('SuperAdmin');

    $postResponse = $this->actingAs($admin)->post('/superadmin/pages', [
        'title' => 'Terms of Service Test',
        'slug' => 'terms-of-service-test',
        'content' => '<h2>Terms and Conditions</h2><p>Here are our platform terms.</p>',
        'meta_title' => 'Terms Test',
        'meta_description' => 'Platform terms test',
        'is_published' => true,
    ]);
    $postResponse->assertRedirect();

    $this->assertDatabaseHas('custom_pages', [
        'slug' => 'terms-of-service-test',
        'title' => 'Terms of Service Test',
        'is_published' => 1,
    ]);

    // Public view
    $publicResponse = $this->get('/page/terms-of-service-test');
    $publicResponse->assertOk();
});

test('public user can submit demo request and superadmin can view it', function () {
    $response = $this->post('/demo-request', [
        'name' => 'John Doe',
        'email' => 'john@acme.com',
        'company_name' => 'Acme Corp',
        'phone' => '+1234567890',
        'team_size' => '11-50',
        'message' => 'Interested in the POS and ERP combo.',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('demo_requests', [
        'email' => 'john@acme.com',
        'company_name' => 'Acme Corp',
        'status' => 'pending',
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('SuperAdmin');

    $adminResponse = $this->actingAs($admin)->get('/superadmin/demo-requests');
    $adminResponse->assertOk();
});

test('superadmin can manage subscription coupons and public api validates coupon', function () {
    $admin = User::factory()->create();
    $admin->assignRole('SuperAdmin');

    $coupon = Coupon::create([
        'code' => 'SAVE25',
        'name' => '25% Discount Test',
        'discount_type' => 'percentage',
        'discount_value' => 25.00,
        'max_uses' => 100,
        'used_count' => 0,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->get('/superadmin/subscriptions/coupons');
    $response->assertOk();

    // Validate valid coupon
    $validResponse = $this->postJson('/api/coupons/validate', [
        'code' => 'SAVE25',
    ]);
    $validResponse->assertOk();
    $validResponse->assertJson([
        'valid' => true,
        'coupon' => [
            'code' => 'SAVE25',
            'discount_type' => 'percentage',
            'discount_value' => 25,
        ],
    ]);

    // Validate invalid coupon
    $invalidResponse = $this->postJson('/api/coupons/validate', [
        'code' => 'NONEXISTENT',
    ]);
    $invalidResponse->assertStatus(422);
    $invalidResponse->assertJson([
        'valid' => false,
    ]);
});

test('tenant admin can update company email notifications and bank transfer settings', function () {
    $tenant = Tenant::factory()->create([
        'status' => TenantStatus::Active,
    ]);
    $user = User::factory()->create();
    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->put('/admin/company-settings', [
        'email_settings' => [
            'enable_custom_smtp' => true,
            'mail_driver' => 'smtp',
            'mail_host' => 'smtp.customdomain.com',
            'mail_port' => '587',
            'mail_username' => 'user@customdomain.com',
            'mail_password' => 'secret123',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'billing@customdomain.com',
            'mail_from_name' => 'Custom Billing',
        ],
        'email_notifications' => [
            'new_user' => true,
            'customer_invoice_send' => true,
            'payment_reminder' => false,
            'invoice_payment_create' => true,
            'proposal_status_updated' => true,
            'new_helpdesk_ticket' => true,
            'new_helpdesk_ticket_reply' => true,
            'purchase_send' => false,
            'purchase_payment_create' => true,
        ],
        'bank_transfer' => [
            'enable_bank_transfer' => true,
            'bank_details' => 'Account: 123-456-7890 Bank: Test Bank',
        ],
    ]);

    $response->assertRedirect();

    $tenant->refresh();
    expect($tenant->settings['email']['enable_custom_smtp'])->toBeTrue()
        ->and($tenant->settings['email']['mail_host'])->toBe('smtp.customdomain.com')
        ->and($tenant->settings['email_notifications']['payment_reminder'])->toBeFalse()
        ->and($tenant->settings['bank_transfer']['enable_bank_transfer'])->toBeTrue()
        ->and($tenant->settings['bank_transfer']['bank_details'])->toBe('Account: 123-456-7890 Bank: Test Bank');
});
