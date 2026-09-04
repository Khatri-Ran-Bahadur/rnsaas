<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Audit\Models\AuditLog;
use Modules\Tenancy\Models\Tenant;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function actingAsAuditSuperAdmin(): User
{
    Role::firstOrCreate([
        'name' => 'SuperAdmin',
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->assignRole('SuperAdmin');

    test()->actingAs($user);

    return $user;
}

it('allows a superadmin to view audit logs', function () {
    actingAsAuditSuperAdmin();

    AuditLog::query()->create([
        'event' => 'subscription.created',
        'created_at' => now(),
    ]);

    $response = $this->get('/admin/audit-logs');

    $response->assertOk();
    $page = $response->viewData('page');
    expect($page['props']['auditLogs']['total'])->toBe(1);
});

it('returns 403 forbidden for normal authenticated users without superadmin role', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/admin/audit-logs');

    $response->assertForbidden();
});

it('redirects unauthenticated guests to login', function () {
    $response = $this->get('/admin/audit-logs');

    $response->assertRedirect('/login');
});

it('paginates audit logs with 20 records per page by default', function () {
    actingAsAuditSuperAdmin();

    for ($i = 1; $i <= 25; $i++) {
        AuditLog::query()->create([
            'event' => 'system.event.'.$i,
            'created_at' => now(),
        ]);
    }

    $responsePage1 = $this->get('/admin/audit-logs');
    $responsePage1->assertOk();

    $logsPage1 = $responsePage1->viewData('page')['props']['auditLogs'];
    expect($logsPage1['total'])->toBe(25);
    expect($logsPage1['per_page'])->toBe(20);
    expect(count($logsPage1['data']))->toBe(20);

    $responsePage2 = $this->get('/admin/audit-logs?page=2');
    $responsePage2->assertOk();

    $logsPage2 = $responsePage2->viewData('page')['props']['auditLogs'];
    expect(count($logsPage2['data']))->toBe(5);
});

it('filters audit logs by event name', function () {
    actingAsAuditSuperAdmin();

    AuditLog::query()->create([
        'event' => 'subscription.created',
        'created_at' => now(),
    ]);

    AuditLog::query()->create([
        'event' => 'subscription.canceled',
        'created_at' => now(),
    ]);

    $response = $this->get('/admin/audit-logs?event=subscription.canceled');
    $response->assertOk();

    $logs = $response->viewData('page')['props']['auditLogs'];
    expect($logs['total'])->toBe(1);
    expect($logs['data'][0]['event'])->toBe('subscription.canceled');
});

it('filters audit logs by tenant', function () {
    actingAsAuditSuperAdmin();

    $tenantA = Tenant::factory()->create(['name' => 'Tenant Alpha', 'slug' => 'tenant-alpha']);
    $tenantB = Tenant::factory()->create(['name' => 'Tenant Beta', 'slug' => 'tenant-beta']);

    AuditLog::query()->create([
        'tenant_id' => $tenantA->id,
        'event' => 'tenant.action.a',
        'created_at' => now(),
    ]);

    AuditLog::query()->create([
        'tenant_id' => $tenantB->id,
        'event' => 'tenant.action.b',
        'created_at' => now(),
    ]);

    $response = $this->get("/admin/audit-logs?tenant_id={$tenantA->id}");
    $response->assertOk();

    $logs = $response->viewData('page')['props']['auditLogs'];
    expect($logs['total'])->toBe(1);
    expect($logs['data'][0]['tenant_id'])->toBe($tenantA->id);
});

it('supports search by event, request id, tenant name, and actor', function () {
    actingAsAuditSuperAdmin();

    $actor = User::factory()->create(['name' => 'Jane Auditor', 'email' => 'jane@example.com']);
    $tenant = Tenant::factory()->create(['name' => 'Acme Corporation', 'slug' => 'acme-corp']);

    AuditLog::query()->create([
        'event' => 'target.special.event',
        'request_id' => 'req-xyz-unique-99',
        'tenant_id' => $tenant->id,
        'actor_type' => $actor->getMorphClass(),
        'actor_id' => $actor->id,
        'created_at' => now(),
    ]);

    AuditLog::query()->create([
        'event' => 'other.unrelated.event',
        'request_id' => 'req-other-1',
        'created_at' => now(),
    ]);

    // 1. Search by event name
    $resEvent = $this->get('/admin/audit-logs?search=special.event');
    $resEvent->assertOk();
    expect($resEvent->viewData('page')['props']['auditLogs']['total'])->toBe(1);

    // 2. Search by request ID
    $resReq = $this->get('/admin/audit-logs?search=xyz-unique-99');
    $resReq->assertOk();
    expect($resReq->viewData('page')['props']['auditLogs']['total'])->toBe(1);

    // 3. Search by tenant name
    $resTenant = $this->get('/admin/audit-logs?search=Acme');
    $resTenant->assertOk();
    expect($resTenant->viewData('page')['props']['auditLogs']['total'])->toBe(1);

    // 4. Search by actor email
    $resActor = $this->get('/admin/audit-logs?search=jane@example.com');
    $resActor->assertOk();
    expect($resActor->viewData('page')['props']['auditLogs']['total'])->toBe(1);
});

it('filters audit logs by date range', function () {
    actingAsAuditSuperAdmin();

    AuditLog::query()->create([
        'event' => 'event.past',
        'created_at' => now()->subDays(10),
    ]);

    AuditLog::query()->create([
        'event' => 'event.recent',
        'created_at' => now()->subDays(2),
    ]);

    AuditLog::query()->create([
        'event' => 'event.today',
        'created_at' => now(),
    ]);

    // Test date_from filter
    $resFrom = $this->get('/admin/audit-logs?date_from='.now()->subDays(3)->toDateString());
    $resFrom->assertOk();
    expect($resFrom->viewData('page')['props']['auditLogs']['total'])->toBe(2);

    // Test date_to filter
    $resTo = $this->get('/admin/audit-logs?date_to='.now()->subDays(5)->toDateString());
    $resTo->assertOk();
    expect($resTo->viewData('page')['props']['auditLogs']['total'])->toBe(1);
    expect($resTo->viewData('page')['props']['auditLogs']['data'][0]['event'])->toBe('event.past');
});

it('preserves query strings across pagination links', function () {
    actingAsAuditSuperAdmin();

    for ($i = 1; $i <= 25; $i++) {
        AuditLog::query()->create([
            'event' => 'membership.invited',
            'created_at' => now(),
        ]);
    }

    $response = $this->get('/admin/audit-logs?event=membership.invited&page=2');
    $response->assertOk();

    $logs = $response->viewData('page')['props']['auditLogs'];
    expect($logs['current_page'])->toBe(2);

    $nextOrPrevLink = collect($logs['links'])->first(fn ($l) => ! empty($l['url']) && str_contains($l['url'], 'page=1'));
    expect($nextOrPrevLink['url'])->toContain('event=membership.invited');
});

it('returns audit detail data with actor, tenant, values, and metadata correctly', function () {
    actingAsAuditSuperAdmin();

    $actor = User::factory()->create(['name' => 'Super Auditor', 'email' => 'auditor@example.com']);
    $tenant = Tenant::factory()->create(['name' => 'Audit Test Corp', 'slug' => 'audit-test-corp']);

    AuditLog::query()->create([
        'tenant_id' => $tenant->id,
        'actor_type' => $actor->getMorphClass(),
        'actor_id' => $actor->id,
        'event' => 'subscription.canceled',
        'old_values' => ['status' => 'active'],
        'new_values' => ['status' => 'canceled'],
        'metadata' => ['module' => 'subscription', 'source' => 'subscription.cancel'],
        'request_id' => 'req-trace-12345',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 TestAgent',
        'created_at' => now(),
    ]);

    $response = $this->get('/admin/audit-logs');
    $response->assertOk();

    $logData = $response->viewData('page')['props']['auditLogs']['data'][0];

    expect($logData['event'])->toBe('subscription.canceled');
    expect($logData['old_values'])->toMatchArray(['status' => 'active']);
    expect($logData['new_values'])->toMatchArray(['status' => 'canceled']);
    expect($logData['metadata'])->toMatchArray(['module' => 'subscription', 'source' => 'subscription.cancel']);
    expect($logData['request_id'])->toBe('req-trace-12345');
    expect($logData['ip_address'])->toBe('127.0.0.1');
    expect($logData['user_agent'])->toBe('Mozilla/5.0 TestAgent');
    expect($logData['actor']['name'])->toBe('Super Auditor');
    expect($logData['actor']['email'])->toBe('auditor@example.com');
    expect($logData['tenant']['name'])->toBe('Audit Test Corp');
    expect($logData['tenant']['slug'])->toBe('audit-test-corp');
});

it('verifies no mutation or delete endpoints exist for audit logs', function () {
    actingAsAuditSuperAdmin();

    $log = AuditLog::query()->create([
        'event' => 'system.immutable',
        'created_at' => now(),
    ]);

    // POST /admin/audit-logs (creation not allowed)
    $this->post('/admin/audit-logs', ['event' => 'hack'])->assertStatus(405);

    // PUT /admin/audit-logs/{id} (mutation not allowed)
    $this->put("/admin/audit-logs/{$log->id}", ['event' => 'tamper'])->assertStatus(404);

    // PATCH /admin/audit-logs/{id} (mutation not allowed)
    $this->patch("/admin/audit-logs/{$log->id}", ['event' => 'tamper'])->assertStatus(404);

    // DELETE /admin/audit-logs/{id} (deletion not allowed)
    $this->delete("/admin/audit-logs/{$log->id}")->assertStatus(404);
});
