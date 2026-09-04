<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaDirectory;
use Modules\Tenancy\Models\Tenant;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function actingAsSuperAdminUser(): User
{
    Role::findOrCreate('SuperAdmin', 'web');

    $user = User::factory()->create([
        'name' => 'Super Admin Media',
        'email' => 'admin.media@sathisaas.test',
        'email_verified_at' => now(),
    ]);

    $user->assignRole('SuperAdmin');

    test()->actingAs($user);

    return $user;
}

beforeEach(function (): void {
    Storage::fake('public');
});

it('allows super admin to view the media library page', function (): void {
    actingAsSuperAdminUser();

    $response = test()->get(route('superadmin.media.page'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Media/Index'));
});

it('allows super admin to fetch media and directories index json', function (): void {
    actingAsSuperAdminUser();

    $dir = MediaDirectory::create([
        'name' => 'Banners',
        'slug' => 'banners',
    ]);

    $media = Media::create([
        'name' => 'logo.png',
        'file_name' => 'logo-123.png',
        'mime_type' => 'image/png',
        'disk' => 'public',
        'size' => 1024,
        'directory_id' => $dir->id,
    ]);

    $response = test()->getJson(route('superadmin.media.index'));

    $response->assertOk()
        ->assertJsonStructure([
            'media',
            'directories',
        ])
        ->assertJsonFragment([
            'name' => 'logo.png',
        ])
        ->assertJsonFragment([
            'name' => 'Banners',
        ]);
});

it('allows super admin to batch upload media files', function (): void {
    actingAsSuperAdminUser();

    $file1 = UploadedFile::fake()->image('banner1.jpg', 600, 400);
    $file2 = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    $response = test()->post(route('superadmin.media.batch'), [
        'files' => [$file1, $file2],
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'media' => [
                '*' => ['id', 'name', 'file_name', 'url', 'size', 'mime_type'],
            ],
        ]);

    test()->assertDatabaseHas('media', [
        'name' => 'banner1.jpg',
    ]);

    test()->assertDatabaseHas('media', [
        'name' => 'document.pdf',
    ]);
});

it('allows super admin to create, rename, and delete directories', function (): void {
    actingAsSuperAdminUser();

    // Create
    $createResponse = test()->postJson(route('superadmin.media.directories.create'), [
        'name' => 'Marketing Assets',
    ]);

    $createResponse->assertOk()
        ->assertJsonFragment(['name' => 'Marketing Assets']);

    test()->assertDatabaseHas('media_directories', [
        'name' => 'Marketing Assets',
    ]);

    $dirId = $createResponse->json('directory.id');

    // Rename
    $renameResponse = test()->putJson(route('superadmin.media.directories.update', $dirId), [
        'name' => 'Promotional Assets',
    ]);

    $renameResponse->assertOk()
        ->assertJsonFragment(['name' => 'Promotional Assets']);

    test()->assertDatabaseHas('media_directories', [
        'id' => $dirId,
        'name' => 'Promotional Assets',
    ]);

    // Delete
    $deleteResponse = test()->deleteJson(route('superadmin.media.directories.destroy', $dirId));

    $deleteResponse->assertOk();

    test()->assertDatabaseMissing('media_directories', [
        'id' => $dirId,
    ]);
});

it('allows super admin to move media to another directory', function (): void {
    actingAsSuperAdminUser();

    $dir = MediaDirectory::create([
        'name' => 'Reports',
        'slug' => 'reports',
    ]);

    $media = Media::create([
        'name' => 'report.pdf',
        'file_name' => 'report-123.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'public',
        'size' => 2048,
        'directory_id' => null,
    ]);

    $response = test()->patchJson(route('superadmin.media.directory.update', $media->id), [
        'directory_id' => $dir->id,
    ]);

    $response->assertOk();

    test()->assertDatabaseHas('media', [
        'id' => $media->id,
        'directory_id' => $dir->id,
    ]);
});

it('allows super admin to delete single and bulk media files', function (): void {
    actingAsSuperAdminUser();

    $media1 = Media::create([
        'name' => 'temp1.png',
        'file_name' => 'temp1-123.png',
        'mime_type' => 'image/png',
        'disk' => 'public',
        'size' => 500,
    ]);

    $media2 = Media::create([
        'name' => 'temp2.png',
        'file_name' => 'temp2-123.png',
        'mime_type' => 'image/png',
        'disk' => 'public',
        'size' => 600,
    ]);

    // Single delete
    $delSingle = test()->deleteJson(route('superadmin.media.destroy', $media1->id));
    $delSingle->assertOk();
    test()->assertDatabaseMissing('media', ['id' => $media1->id]);

    // Bulk delete
    $delBulk = test()->postJson(route('superadmin.media.batch-destroy'), [
        'ids' => [$media2->id],
    ]);
    $delBulk->assertOk();
    test()->assertDatabaseMissing('media', ['id' => $media2->id]);
});

it('supports multi-tenant scoping for media isolation', function (): void {
    actingAsSuperAdminUser();

    $tenantA = Tenant::factory()->create(['name' => 'Tenant A']);
    $tenantB = Tenant::factory()->create(['name' => 'Tenant B']);

    $mediaA = Media::create([
        'tenant_id' => $tenantA->id,
        'name' => 'tenant-a-doc.pdf',
        'file_name' => 'tenant-a-doc.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
    ]);

    $mediaB = Media::create([
        'tenant_id' => $tenantB->id,
        'name' => 'tenant-b-doc.pdf',
        'file_name' => 'tenant-b-doc.pdf',
        'mime_type' => 'application/pdf',
        'size' => 200,
    ]);

    $platformMedia = Media::create([
        'tenant_id' => null,
        'name' => 'platform-doc.pdf',
        'file_name' => 'platform-doc.pdf',
        'mime_type' => 'application/pdf',
        'size' => 300,
    ]);

    // Request Tenant A media
    $resA = test()->getJson(route('superadmin.media.index', ['tenant_id' => $tenantA->id]));
    $resA->assertOk()
        ->assertJsonFragment(['name' => 'tenant-a-doc.pdf'])
        ->assertJsonMissing(['name' => 'tenant-b-doc.pdf'])
        ->assertJsonMissing(['name' => 'platform-doc.pdf']);

    // Request Platform media (no tenant_id)
    $resPlatform = test()->getJson(route('superadmin.media.index'));
    $resPlatform->assertOk()
        ->assertJsonFragment(['name' => 'platform-doc.pdf'])
        ->assertJsonMissing(['name' => 'tenant-a-doc.pdf'])
        ->assertJsonMissing(['name' => 'tenant-b-doc.pdf']);
});
