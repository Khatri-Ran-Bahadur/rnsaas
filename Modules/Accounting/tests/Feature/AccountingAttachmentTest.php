<?php

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Accounting\Application\Actions\Attachments\DeleteAccountingAttachmentAction;
use Modules\Accounting\Application\Actions\Attachments\UploadAccountingAttachmentAction;
use Modules\Accounting\Models\AccountingAttachment;
use Modules\Accounting\Models\CustomerPayment;
use Modules\Accounting\Models\SalesInvoice;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(RefreshDatabase::class);

function createTenant(): Tenant
{
    return Tenant::factory()->create();
}

function createUser(?Tenant $tenant = null): User
{
    $user = User::factory()->create();
    $targetTenant = $tenant ?? Tenant::latest('id')->first();

    if ($targetTenant) {
        TenantMembership::firstOrCreate([
            'tenant_id' => $targetTenant->id,
            'user_id' => $user->id,
        ], [
            'status' => 'active',
        ]);
        session(['current_tenant_id' => $targetTenant->id]);
        app(CurrentTenant::class)->set($targetTenant);
    }

    return $user;
}

beforeEach(function () {
    Storage::fake('private');
});

it('can upload an attachment to a sales invoice', function () {
    $tenant = createTenant();
    $user = createUser();

    $this->actingAs($user);

    app(CurrentTenant::class)->set($tenant);

    $invoice = SalesInvoice::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $file = UploadedFile::fake()->create(
        'invoice.pdf',
        500,
        'application/pdf'
    );

    $attachment = app(
        UploadAccountingAttachmentAction::class
    )->execute(
        attachable: $invoice,
        file: $file,
        currentTenant: app(CurrentTenant::class),
    );

    expect($attachment)
        ->tenant_id->toBe($tenant->id)
        ->original_name->toBe('invoice.pdf')
        ->mime_type->toBe('application/pdf');

    Storage::disk('private')->assertExists($attachment->path);
});

it('can upload a payment slip', function () {
    $tenant = createTenant();
    $user = createUser();

    $this->actingAs($user);

    app(CurrentTenant::class)->set($tenant);

    $payment = CustomerPayment::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $file = UploadedFile::fake()->create(
        'bank-slip.jpg',
        300,
        'image/jpeg'
    );

    $attachment = app(
        UploadAccountingAttachmentAction::class
    )->execute(
        attachable: $payment,
        file: $file,
        currentTenant: app(CurrentTenant::class),
    );

    expect($attachment->original_name)
        ->toBe('bank-slip.jpg');

    Storage::disk('private')->assertExists($attachment->path);
});

it('rejects unsupported file types', function () {
    $tenant = createTenant();
    $user = createUser();

    $this->actingAs($user);

    $invoice = SalesInvoice::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $response = $this->post(
        route('admin.accounting.invoices.attachments.store', $invoice),
        [
            'file' => UploadedFile::fake()->create(
                'malware.exe',
                100,
                'application/octet-stream'
            ),
        ]
    );

    $response->assertSessionHasErrors('file');
});

it('rejects files larger than 10 MB', function () {
    $tenant = createTenant();
    $user = createUser();

    $this->actingAs($user);

    $invoice = SalesInvoice::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $response = $this->post(
        route('admin.accounting.invoices.attachments.store', $invoice),
        [
            'file' => UploadedFile::fake()->create(
                'large.pdf',
                11000,
                'application/pdf'
            ),
        ]
    );

    $response->assertSessionHasErrors('file');
});

it('cannot upload an attachment to another tenant invoice', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();

    $user = createUser();

    $this->actingAs($user);

    app(CurrentTenant::class)->set($tenantA);

    $invoice = SalesInvoice::factory()->create([
        'tenant_id' => $tenantB->id,
    ]);

    $file = UploadedFile::fake()->create(
        'invoice.pdf',
        500,
        'application/pdf'
    );

    expect(fn () => app(
        UploadAccountingAttachmentAction::class
    )->execute(
        attachable: $invoice,
        file: $file,
        currentTenant: app(CurrentTenant::class),
    ))->toThrow(NotFoundHttpException::class);
});

it('deletes the physical file when attachment is deleted', function () {
    $tenant = createTenant();
    $user = createUser();

    $this->actingAs($user);

    app(CurrentTenant::class)->set($tenant);

    $invoice = SalesInvoice::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $file = UploadedFile::fake()->create(
        'invoice.pdf',
        500,
        'application/pdf'
    );

    $attachment = app(
        UploadAccountingAttachmentAction::class
    )->execute(
        attachable: $invoice,
        file: $file,
        currentTenant: app(CurrentTenant::class),
    );

    Storage::disk('private')->assertExists($attachment->path);

    app(
        DeleteAccountingAttachmentAction::class
    )->execute(
        attachment: $attachment,
        currentTenant: app(CurrentTenant::class),
    );

    Storage::disk('private')->assertMissing($attachment->path);

    expect(AccountingAttachment::find($attachment->id))
        ->toBeNull();
});
