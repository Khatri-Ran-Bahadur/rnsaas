<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Tenancy\Domain\Enums\BranchStatus;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

uses(RefreshDatabase::class);

function createTenantAdmin(string $tenantName = 'Test Organization'): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'name' => $tenantName,
        'status' => TenantStatus::Active,
    ]);

    $membership = TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    return [$user, $tenant, $membership];
}

// 1. Admin can view own organization's branches
it('allows organization admin to view own organization branches', function (): void {
    [$user, $tenant] = createTenantAdmin('Alpha Org');

    Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Kathmandu Hub',
        'code' => 'KTM-01',
    ]);

    $response = $this->actingAs($user)->get('/admin/branches');

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Branches/Index')
            ->has('branches.data', 1)
            ->where('branches.data.0.name', 'Kathmandu Hub')
            ->where('branches.data.0.code', 'KTM-01')
    );
});

// 2. Admin cannot view another organization's branch
it('prevents organization admin from viewing another organization branch in list or show', function (): void {
    [$userA, $tenantA] = createTenantAdmin('Org A');
    [$userB, $tenantB] = createTenantAdmin('Org B');

    $branchA = Branch::factory()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'Branch of A',
        'code' => 'BRA-01',
    ]);

    $branchB = Branch::factory()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Branch of B',
        'code' => 'BRB-01',
    ]);

    // Index view only shows Branch A
    $indexResponse = $this->actingAs($userA)->get('/admin/branches');
    $indexResponse->assertSuccessful();
    $indexResponse->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Branches/Index')
            ->has('branches.data', 1)
            ->where('branches.data.0.name', 'Branch of A')
    );

    // Direct access to Branch B returns 404
    $showResponse = $this->actingAs($userA)->get("/admin/branches/{$branchB->public_id}");
    $showResponse->assertNotFound();
});

// 3. Admin can create branch
it('allows organization admin to create a branch', function (): void {
    [$user, $tenant] = createTenantAdmin();

    $response = $this->actingAs($user)->post('/admin/branches', [
        'name' => 'Pokhara Branch',
        'code' => 'pkr-01',
        'address_line_1' => 'Lakeside Road',
        'city' => 'Pokhara',
        'state' => 'Gandaki',
        'postal_code' => '33700',
        'country_code' => 'np',
    ]);

    $response->assertRedirect(route('admin.branches.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('branches', [
        'tenant_id' => $tenant->id,
        'name' => 'Pokhara Branch',
        'code' => 'PKR-01',
        'city' => 'Pokhara',
        'country_code' => 'NP',
        'status' => BranchStatus::Active->value,
    ]);
});

// 4. tenant_id is automatically taken from CurrentTenant
it('automatically sets tenant_id from CurrentTenant when creating a branch', function (): void {
    [$user, $tenant] = createTenantAdmin();

    $this->actingAs($user)->post('/admin/branches', [
        'name' => 'Biratnagar Branch',
        'code' => 'BRT-01',
    ]);

    $branch = Branch::where('code', 'BRT-01')->firstOrFail();
    expect($branch->tenant_id)->toBe($tenant->id);
});

// 5. Request cannot force creation under another tenant
it('ignores any attempt to tamper tenant_id in request body', function (): void {
    [$user, $tenantA] = createTenantAdmin('Org A');
    [$userOther, $tenantB] = createTenantAdmin('Org B');

    $this->actingAs($user)->post('/admin/branches', [
        'name' => 'Hacked Branch',
        'code' => 'HCK-01',
        'tenant_id' => $tenantB->id,
    ]);

    $branch = Branch::where('code', 'HCK-01')->firstOrFail();
    expect($branch->tenant_id)->toBe($tenantA->id);
    expect($branch->tenant_id)->not->toBe($tenantB->id);
});

// 6. Duplicate branch code inside same organization fails
it('fails validation when creating duplicate branch code inside the same organization', function (): void {
    [$user, $tenant] = createTenantAdmin();

    Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'code' => 'KTM-01',
    ]);

    $response = $this->actingAs($user)->post('/admin/branches', [
        'name' => 'Second KTM Branch',
        'code' => 'ktm-01',
    ]);

    $response->assertSessionHasErrors('code');
    expect(Branch::where('tenant_id', $tenant->id)->count())->toBe(1);
});

// 7. Same branch code in different organizations is allowed
it('allows the same branch code in different organizations', function (): void {
    [$userA, $tenantA] = createTenantAdmin('Org A');
    [$userB, $tenantB] = createTenantAdmin('Org B');

    Branch::factory()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'Branch Org A',
        'code' => 'HQ-01',
    ]);

    $response = $this->actingAs($userB)->post('/admin/branches', [
        'name' => 'Branch Org B',
        'code' => 'HQ-01',
    ]);

    $response->assertRedirect(route('admin.branches.index'));
    $response->assertSessionHasNoErrors();

    expect(Branch::where('code', 'HQ-01')->count())->toBe(2);
});

// 8. Admin can update own branch
it('allows organization admin to update own branch', function (): void {
    [$user, $tenant] = createTenantAdmin();

    $branch = Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Old Name',
        'code' => 'OLD-01',
        'status' => BranchStatus::Active,
    ]);

    $response = $this->actingAs($user)->put("/admin/branches/{$branch->public_id}", [
        'name' => 'Updated Branch Name',
        'code' => 'NEW-01',
        'status' => 'inactive',
        'city' => 'Lalitpur',
    ]);

    $response->assertRedirect(route('admin.branches.index'));
    $response->assertSessionHas('success');

    $branch->refresh();
    expect($branch->name)->toBe('Updated Branch Name');
    expect($branch->code)->toBe('NEW-01');
    expect($branch->status)->toBe(BranchStatus::Inactive);
    expect($branch->city)->toBe('Lalitpur');
});

// 9. Admin cannot update another organization's branch
it('prevents organization admin from updating another organization branch', function (): void {
    [$userA, $tenantA] = createTenantAdmin('Org A');
    [$userB, $tenantB] = createTenantAdmin('Org B');

    $branchB = Branch::factory()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Branch B Original',
        'code' => 'BRB-01',
    ]);

    $response = $this->actingAs($userA)->put("/admin/branches/{$branchB->public_id}", [
        'name' => 'Malicious Update',
        'code' => 'MAL-01',
        'status' => 'active',
    ]);

    $response->assertNotFound();

    $branchB->refresh();
    expect($branchB->name)->toBe('Branch B Original');
});

// 10. Admin can deactivate branch
it('allows admin to deactivate branch without hard deleting', function (): void {
    [$user, $tenant] = createTenantAdmin();

    $branch = Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => BranchStatus::Active,
    ]);

    $response = $this->actingAs($user)->patch("/admin/branches/{$branch->public_id}/deactivate");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $branch->refresh();
    expect($branch->status)->toBe(BranchStatus::Inactive);
    $this->assertDatabaseHas('branches', ['id' => $branch->id]);
});

// 11. Admin can reactivate branch
it('allows admin to reactivate an inactive branch', function (): void {
    [$user, $tenant] = createTenantAdmin();

    $branch = Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => BranchStatus::Inactive,
    ]);

    $response = $this->actingAs($user)->patch("/admin/branches/{$branch->public_id}/activate");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $branch->refresh();
    expect($branch->status)->toBe(BranchStatus::Active);
});

// 12. Search works
it('filters branches by search query matching name or code', function (): void {
    [$user, $tenant] = createTenantAdmin();

    Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Kathmandu North',
        'code' => 'KTM-N',
    ]);

    Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pokhara South',
        'code' => 'PKR-S',
    ]);

    // Search by name
    $responseByName = $this->actingAs($user)->get('/admin/branches?search=North');
    $responseByName->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Branches/Index')
            ->has('branches.data', 1)
            ->where('branches.data.0.code', 'KTM-N')
    );

    // Search by code
    $responseByCode = $this->actingAs($user)->get('/admin/branches?search=PKR');
    $responseByCode->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Branches/Index')
            ->has('branches.data', 1)
            ->where('branches.data.0.name', 'Pokhara South')
    );
});

// 13. Status filter works
it('filters branches by operational status', function (): void {
    [$user, $tenant] = createTenantAdmin();

    Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Active Branch',
        'code' => 'ACT-01',
        'status' => BranchStatus::Active,
    ]);

    Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Inactive Branch',
        'code' => 'INA-01',
        'status' => BranchStatus::Inactive,
    ]);

    $activeResponse = $this->actingAs($user)->get('/admin/branches?status=active');
    $activeResponse->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Branches/Index')
            ->has('branches.data', 1)
            ->where('branches.data.0.code', 'ACT-01')
    );

    $inactiveResponse = $this->actingAs($user)->get('/admin/branches?status=inactive');
    $inactiveResponse->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Branches/Index')
            ->has('branches.data', 1)
            ->where('branches.data.0.code', 'INA-01')
    );
});

// 14. Pagination works
it('paginates branches on the server side', function (): void {
    [$user, $tenant] = createTenantAdmin();

    // Create 18 branches
    Branch::factory()->count(18)->create([
        'tenant_id' => $tenant->id,
    ]);

    $page1Response = $this->actingAs($user)->get('/admin/branches');
    $page1Response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Branches/Index')
            ->has('branches.data', 15)
            ->where('branches.total', 18)
            ->where('branches.current_page', 1)
            ->where('branches.last_page', 2)
    );

    $page2Response = $this->actingAs($user)->get('/admin/branches?page=2');
    $page2Response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Branches/Index')
            ->has('branches.data', 3)
            ->where('branches.current_page', 2)
    );
});

it('supports custom per_page parameter on branch pagination', function (): void {
    [$user, $tenant] = createTenantAdmin();

    Branch::factory()->count(25)->create([
        'tenant_id' => $tenant->id,
    ]);

    $response = $this->actingAs($user)->get('/admin/branches?per_page=10');
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Admin/Branches/Index')
            ->has('branches.data', 10)
            ->where('branches.per_page', 10)
            ->where('branches.last_page', 3)
            ->where('filters.per_page', 10)
    );
});

// 15. Unauthenticated user cannot access Branch Management
it('redirects unauthenticated users from branch management routes to login', function (): void {
    $this->get('/admin/branches')->assertRedirect('/login');
    $this->get('/admin/branches/create')->assertRedirect('/login');
    $this->post('/admin/branches', [])->assertRedirect('/login');
    $this->get('/admin/branches/any-uuid')->assertRedirect('/login');
    $this->get('/admin/branches/any-uuid/edit')->assertRedirect('/login');
    $this->put('/admin/branches/any-uuid', [])->assertRedirect('/login');
    $this->patch('/admin/branches/any-uuid/activate', [])->assertRedirect('/login');
    $this->patch('/admin/branches/any-uuid/deactivate', [])->assertRedirect('/login');
});
