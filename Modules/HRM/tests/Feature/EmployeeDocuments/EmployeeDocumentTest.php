<?php

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\HRM\Domain\Enums\EmployeeDocumentType;
use Modules\HRM\Models\EmployeeDocument;
use Modules\Tenancy\Domain\Constants\OrganizationPermissions;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantRole;
use Modules\Tenancy\Models\TenantRolePermission;
use Modules\Tenancy\Models\TenantStaff;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('private');

    $this->tenant = Tenant::factory()->create();

    $this->user = User::factory()->create();

    $this->role = TenantRole::query()->firstOrCreate(
        [
            'tenant_id' => $this->tenant->id,
            'slug' => 'admin',
        ],
        [
            'name' => 'Admin',
            'description' => 'Organization administrator',
            'is_system' => true,
            'is_active' => true,
        ]
    );

    TenantMembership::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'role_id' => $this->role->id,
        'status' => 'active',
    ]);

    $this->actingAs($this->user);

    session(['current_tenant_id' => $this->tenant->id]);
    app(CurrentTenant::class)->set($this->tenant);

    $this->staff = TenantStaff::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'employment_status' => 'active',
    ]);
});

/*
|--------------------------------------------------------------------------
| Index / Listing
|--------------------------------------------------------------------------
*/

it('can list employee documents with tenant isolation', function (): void {
    $doc = EmployeeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $otherTenant = Tenant::factory()->create();
    $otherStaff = TenantStaff::factory()->create([
        'tenant_id' => $otherTenant->id,
        'employment_status' => 'active',
    ]);
    $otherDoc = EmployeeDocument::factory()->create([
        'tenant_id' => $otherTenant->id,
        'tenant_staff_id' => $otherStaff->id,
    ]);

    $response = $this->get(route('admin.hrm.employee-documents.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('HRM/EmployeeDocuments/Index')
        ->has('documents.data', 1)
        ->where('documents.data.0.public_id', $doc->public_id)
        ->has('stats')
        ->has('staff_members')
        ->has('document_types')
        ->has('document_statuses')
    );
});

it('can filter employee documents by status, type, and expiring days', function (): void {
    $pendingDoc = EmployeeDocument::factory()->pending()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'type' => EmployeeDocumentType::EMPLOYMENT_CONTRACT,
        'expiry_date' => now()->addDays(15),
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $verifiedDoc = EmployeeDocument::factory()->verified()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'type' => EmployeeDocumentType::PASSPORT,
        'expiry_date' => now()->addDays(90),
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    // Filter by status pending
    $response = $this->get(route('admin.hrm.employee-documents.index', [
        'status' => 'pending',
    ]));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('documents.data', 1)
        ->where('documents.data.0.public_id', $pendingDoc->public_id)
    );

    // Filter by expiring_within_days: 30
    $response = $this->get(route('admin.hrm.employee-documents.index', [
        'expiring_within_days' => 30,
    ]));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('documents.data', 1)
        ->where('documents.data.0.public_id', $pendingDoc->public_id)
    );
});

/*
|--------------------------------------------------------------------------
| Create / Store
|--------------------------------------------------------------------------
*/

it('can view employee document create page', function (): void {
    $response = $this->get(route('admin.hrm.employee-documents.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('HRM/EmployeeDocuments/Create')
        ->has('staff_members')
        ->has('document_types')
    );
});

it('can upload a new employee document', function (): void {
    $file = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');

    $response = $this->post(route('admin.hrm.employee-documents.store'), [
        'tenant_staff_id' => $this->staff->id,
        'type' => EmployeeDocumentType::EMPLOYMENT_CONTRACT->value,
        'title' => 'Software Engineer Contract 2026',
        'document_number' => 'SEC-2026-001',
        'issue_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'file' => $file,
        'notes' => 'Permanent full-time contract.',
    ]);

    $response->assertRedirect(route('admin.hrm.employee-documents.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('employee_documents', [
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'title' => 'Software Engineer Contract 2026',
        'document_number' => 'SEC-2026-001',
        'file_disk' => 'private',
        'original_file_name' => 'contract.pdf',
    ]);

    $document = EmployeeDocument::query()->where('title', 'Software Engineer Contract 2026')->first();
    expect($document)->not->toBeNull();
    Storage::disk('private')->assertExists($document->file_path);
});

/*
|--------------------------------------------------------------------------
| Show / Edit / Update
|--------------------------------------------------------------------------
*/

it('can view employee document details', function (): void {
    $doc = EmployeeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->get(route('admin.hrm.employee-documents.show', $doc));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('HRM/EmployeeDocuments/Show')
        ->where('document.data.public_id', $doc->public_id)
        ->has('can')
    );
});

it('can view edit employee document page', function (): void {
    $doc = EmployeeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->get(route('admin.hrm.employee-documents.edit', $doc));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('HRM/EmployeeDocuments/Edit')
        ->where('document.data.public_id', $doc->public_id)
        ->has('staff_members')
        ->has('document_types')
    );
});

it('can update employee document metadata without replacing file', function (): void {
    $doc = EmployeeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'title' => 'Original Title',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->put(route('admin.hrm.employee-documents.update', $doc), [
        'tenant_staff_id' => $this->staff->id,
        'type' => $doc->type->value,
        'title' => 'Updated Contract Title',
        'document_number' => 'NEW-DOC-123',
        'issue_date' => '2026-02-01',
        'expiry_date' => '2027-02-01',
        'is_active' => true,
        'notes' => 'Updated notes.',
    ]);

    $response->assertRedirect(route('admin.hrm.employee-documents.show', $doc));
    $response->assertSessionHas('success');

    $doc->refresh();
    expect($doc->title)->toBe('Updated Contract Title')
        ->and($doc->document_number)->toBe('NEW-DOC-123');
});

/*
|--------------------------------------------------------------------------
| Toggle Status & Delete
|--------------------------------------------------------------------------
*/

it('can toggle employee document active status', function (): void {
    $doc = EmployeeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'is_active' => true,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->patch(route('admin.hrm.employee-documents.toggle-status', $doc));

    $response->assertStatus(302);
    $doc->refresh();
    expect($doc->is_active)->toBeFalse();

    // Toggle back
    $this->patch(route('admin.hrm.employee-documents.toggle-status', $doc));
    $doc->refresh();
    expect($doc->is_active)->toBeTrue();
});

it('can delete an employee document', function (): void {
    $doc = EmployeeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->delete(route('admin.hrm.employee-documents.destroy', $doc));

    $response->assertRedirect(route('admin.hrm.employee-documents.index'));
    $this->assertDatabaseMissing('employee_documents', [
        'id' => $doc->id,
    ]);
});

/*
|--------------------------------------------------------------------------
| Secure Download
|--------------------------------------------------------------------------
*/

it('can securely download an employee document file', function (): void {
    $filePath = 'employee-documents/test/contract.pdf';
    Storage::disk('private')->put($filePath, 'Fake PDF file content');

    $doc = EmployeeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'file_disk' => 'private',
        'file_path' => $filePath,
        'original_file_name' => 'contract.pdf',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->get(route('admin.hrm.employee-documents.download', $doc));

    $response->assertOk();
    $response->assertDownload('contract.pdf');
});

/*
|--------------------------------------------------------------------------
| Dynamic Tenant Permissions & Access Control
|--------------------------------------------------------------------------
*/

it('forbids staff without employee_documents.view permission from listing documents', function (): void {
    // Create a restricted role without employee_documents.view
    $customRole = TenantRole::query()->create([
        'tenant_id' => $this->tenant->id,
        'slug' => 'restricted-staff',
        'name' => 'Restricted Staff',
        'is_system' => false,
        'is_active' => true,
    ]);

    // Assign only dashboard view
    TenantRolePermission::query()->create([
        'tenant_role_id' => $customRole->id,
        'permission' => OrganizationPermissions::DASHBOARD_VIEW,
    ]);

    $restrictedUser = User::factory()->create();
    TenantMembership::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $restrictedUser->id,
        'role_id' => $customRole->id,
        'status' => 'active',
    ]);

    $this->actingAs($restrictedUser);

    $response = $this->get(route('admin.hrm.employee-documents.index'));
    $response->assertForbidden();
});

it('forbids staff without employee_documents.manage permission from creating documents', function (): void {
    $viewerRole = TenantRole::query()->create([
        'tenant_id' => $this->tenant->id,
        'slug' => 'viewer-role',
        'name' => 'Viewer Role',
        'is_system' => false,
        'is_active' => true,
    ]);

    // Give only view permission
    TenantRolePermission::query()->create([
        'tenant_role_id' => $viewerRole->id,
        'permission' => OrganizationPermissions::EMPLOYEE_DOCUMENTS_VIEW,
    ]);

    $viewerUser = User::factory()->create();
    TenantMembership::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $viewerUser->id,
        'role_id' => $viewerRole->id,
        'status' => 'active',
    ]);

    $this->actingAs($viewerUser);

    // Can view list
    $this->get(route('admin.hrm.employee-documents.index'))->assertOk();

    // Cannot create
    $this->get(route('admin.hrm.employee-documents.create'))->assertForbidden();

    $file = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');
    $this->post(route('admin.hrm.employee-documents.store'), [
        'tenant_staff_id' => $this->staff->id,
        'type' => EmployeeDocumentType::EMPLOYMENT_CONTRACT->value,
        'title' => 'Unauthorized Document',
        'file' => $file,
    ])->assertForbidden();
});
