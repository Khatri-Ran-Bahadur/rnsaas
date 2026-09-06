<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Tenancy\Application\Actions\Tenant\ProvisionTenantDefaultsAction;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\Designation;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantRole;
use Modules\Tenancy\Models\TenantStaff;

uses(RefreshDatabase::class);

function createMemberTenantAdmin(string $tenantName = 'Test Organization'): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'name' => $tenantName,
        'status' => TenantStatus::Active,
    ]);

    app(ProvisionTenantDefaultsAction::class)->handle($tenant);

    $adminRole = TenantRole::query()->where('tenant_id', $tenant->id)->where('slug', 'admin')->first();

    $membership = TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role_id' => $adminRole?->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    return [$user, $tenant, $membership, $adminRole];
}

it('allows organization admin to view own organization members with search and filters', function (): void {
    [$admin, $tenant] = createMemberTenantAdmin();

    $otherUser = User::factory()->create(['name' => 'Bimala Sharma', 'email' => 'bimala@example.com']);
    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $otherUser->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get('/admin/members');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Members/Index')
            ->has('members.data', 2)
            ->has('roles')
    );
});

it('filters members by search keyword', function (): void {
    [$admin, $tenant] = createMemberTenantAdmin();

    $user1 = User::factory()->create(['name' => 'Alice Cooper', 'email' => 'alice@rock.com']);
    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user1->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $user2 = User::factory()->create(['name' => 'Bob Marley', 'email' => 'bob@reggae.com']);
    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user2->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $response = $this->actingAs($admin)->get('/admin/members?search=Alice');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Members/Index')
            ->has('members.data', 1)
            ->where('members.data.0.user.name', 'Alice Cooper')
    );
});

it('strictly isolates members between tenants', function (): void {
    [$adminA, $tenantA] = createMemberTenantAdmin('Tenant A');
    [$adminB, $tenantB] = createMemberTenantAdmin('Tenant B');

    $userA = User::factory()->create(['name' => 'User In A']);
    TenantMembership::factory()->create(['tenant_id' => $tenantA->id, 'user_id' => $userA->id, 'status' => TenantMembershipStatus::Active]);

    $userB = User::factory()->create(['name' => 'User In B']);
    TenantMembership::factory()->create(['tenant_id' => $tenantB->id, 'user_id' => $userB->id, 'status' => TenantMembershipStatus::Active]);

    $response = $this->actingAs($adminA)->get('/admin/members');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Members/Index')
            ->has('members.data', 2) // adminA + userA
    );
});

it('displays detailed member profile with account, organization, and employment info', function (): void {
    [$admin, $tenant] = createMemberTenantAdmin();

    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $dept = Department::factory()->create(['tenant_id' => $tenant->id]);
    $desig = Designation::factory()->create(['tenant_id' => $tenant->id]);

    $staffUser = User::factory()->create(['name' => 'John Staff', 'email' => 'staff@example.com']);
    $role = TenantRole::query()->where('tenant_id', $tenant->id)->where('slug', 'staff')->first();

    $membership = TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $staffUser->id,
        'role_id' => $role?->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    TenantStaff::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $staffUser->id,
        'branch_id' => $branch->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
        'employee_code' => 'EMP-001',
    ]);

    $response = $this->actingAs($admin)->get("/admin/members/{$membership->id}");

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Members/Show')
            ->where('member.id', $membership->id)
            ->where('member.user.email', 'staff@example.com')
            ->where('member.staff.employee_code', 'EMP-001')
    );
});

it('re-uses existing global user when adding member by email without duplicating user record', function (): void {
    [$admin, $tenant] = createMemberTenantAdmin();

    $existingGlobalUser = User::factory()->create([
        'name' => 'Existing Global Person',
        'email' => 'existing@global.com',
    ]);

    $initialUserCount = User::count();

    $role = TenantRole::query()->where('tenant_id', $tenant->id)->where('slug', 'staff')->firstOrFail();

    $response = $this->actingAs($admin)->post('/admin/members', [
        'email' => 'existing@global.com',
        'role_id' => $role->id,
        'status' => 'active',
    ]);

    $response->assertRedirect('/admin/members');

    // Strictly NO duplicate user record
    expect(User::count())->toBe($initialUserCount);

    $this->assertDatabaseHas('tenant_user', [
        'tenant_id' => $tenant->id,
        'user_id' => $existingGlobalUser->id,
        'role_id' => $role->id,
        'status' => TenantMembershipStatus::Active->value,
    ]);
});

it('creates new global user when adding member with new email', function (): void {
    [$admin, $tenant] = createMemberTenantAdmin();

    $role = TenantRole::query()->where('tenant_id', $tenant->id)->where('slug', 'staff')->firstOrFail();

    $response = $this->actingAs($admin)->post('/admin/members', [
        'email' => 'newcolleague@example.com',
        'name' => 'New Colleague',
        'role_id' => $role->id,
        'status' => 'active',
    ]);

    $response->assertRedirect('/admin/members');

    $this->assertDatabaseHas('users', [
        'email' => 'newcolleague@example.com',
        'name' => 'New Colleague',
    ]);

    $newUser = User::where('email', 'newcolleague@example.com')->firstOrFail();

    $this->assertDatabaseHas('tenant_user', [
        'tenant_id' => $tenant->id,
        'user_id' => $newUser->id,
        'role_id' => $role->id,
        'status' => TenantMembershipStatus::Active->value,
    ]);
});

it('allows admin to update a member role', function (): void {
    [$admin, $tenant] = createMemberTenantAdmin();

    $user = User::factory()->create();
    $staffRole = TenantRole::query()->where('tenant_id', $tenant->id)->where('slug', 'staff')->firstOrFail();
    $managerRole = TenantRole::query()->where('tenant_id', $tenant->id)->where('slug', 'manager')->firstOrFail();

    $membership = TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role_id' => $staffRole->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $response = $this->actingAs($admin)->put("/admin/members/{$membership->id}", [
        'role_id' => $managerRole->id,
    ]);

    $response->assertRedirect();
    expect($membership->refresh()->role_id)->toBe($managerRole->id);
});

it('allows admin to suspend, reactivate, and revoke a member', function (): void {
    [$admin, $tenant] = createMemberTenantAdmin();

    $user = User::factory()->create();
    $membership = TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
        'joined_at' => now(),
    ]);

    // Suspend
    $this->actingAs($admin)
        ->patch("/admin/members/{$membership->id}/suspend")
        ->assertRedirect();

    expect($membership->refresh()->status)->toBe(TenantMembershipStatus::Suspended);

    // Reactivate
    $this->actingAs($admin)
        ->patch("/admin/members/{$membership->id}/reactivate")
        ->assertRedirect();

    expect($membership->refresh()->status)->toBe(TenantMembershipStatus::Active);

    // Revoke
    $this->actingAs($admin)
        ->delete("/admin/members/{$membership->id}/revoke")
        ->assertRedirect();

    expect($membership->refresh()->status)->toBe(TenantMembershipStatus::Revoked);
});

it('prevents an admin from suspending or revoking their own membership', function (): void {
    [$admin, $tenant, $membership] = createMemberTenantAdmin();

    $this->actingAs($admin)
        ->patch("/admin/members/{$membership->id}/suspend")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($membership->refresh()->status)->toBe(TenantMembershipStatus::Active);

    $this->actingAs($admin)
        ->delete("/admin/members/{$membership->id}/revoke")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($membership->refresh()->status)->toBe(TenantMembershipStatus::Active);
});

it('handles invitation flow: invite, resend, accept, and expiration', function (): void {
    [$admin, $tenant] = createMemberTenantAdmin();
    $role = TenantRole::query()->where('tenant_id', $tenant->id)->where('slug', 'staff')->firstOrFail();

    // 1. Send invitation
    $response = $this->actingAs($admin)->post('/admin/invitations', [
        'email' => 'invitee@example.com',
        'name' => 'Invited Person',
        'role_id' => $role->id,
    ]);

    $response->assertRedirect('/admin/invitations');

    $invitedUser = User::where('email', 'invitee@example.com')->firstOrFail();
    $membership = TenantMembership::where('tenant_id', $tenant->id)->where('user_id', $invitedUser->id)->firstOrFail();

    expect($membership->status)->toBe(TenantMembershipStatus::Invited);
    expect($membership->invitation_token)->not->toBeNull();
    expect($membership->expires_at)->not->toBeNull();

    $initialToken = $membership->invitation_token;

    // 2. Resend invitation
    $this->actingAs($admin)->post("/admin/invitations/{$membership->id}/resend")->assertRedirect();
    $membership->refresh();
    expect($membership->invitation_token)->not->toBe($initialToken);

    // 3. Accept invitation via public link
    $acceptResponse = $this->get("/invitations/{$membership->invitation_token}/accept");
    $acceptResponse->assertRedirect('/admin/dashboard');

    $membership->refresh();
    expect($membership->status)->toBe(TenantMembershipStatus::Active);
    expect($membership->joined_at)->not->toBeNull();
    expect($membership->invitation_token)->toBeNull();
});

it('rejects expired invitation link', function (): void {
    [$admin, $tenant] = createMemberTenantAdmin();
    $role = TenantRole::query()->where('tenant_id', $tenant->id)->where('slug', 'staff')->firstOrFail();

    $user = User::factory()->create();
    $membership = TenantMembership::create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role_id' => $role->id,
        'status' => TenantMembershipStatus::Invited,
        'invitation_token' => 'expired-token-1234567890',
        'invited_at' => now()->subDays(10),
        'expires_at' => now()->subDays(3),
        'version' => 1,
    ]);

    $response = $this->get('/invitations/expired-token-1234567890/accept');
    $response->assertRedirect('/login');
    $response->assertSessionHas('error');

    expect($membership->refresh()->status)->toBe(TenantMembershipStatus::Invited);
});
