<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    Role::firstOrCreate(['name' => 'SuperAdmin']);
    Role::firstOrCreate(['name' => 'Admin']);

    $this->tenant = Tenant::factory()->create([
        'status' => TenantStatus::Active,
        'name' => 'Acme Corporation',
    ]);

    $this->user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@acme.com',
        'password' => Hash::make('OldPassword123!'),
    ]);

    $this->membership = TenantMembership::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $this->superAdmin = User::factory()->create([
        'name' => 'Super Administrator',
        'email' => 'super@admin.com',
        'password' => Hash::make('SuperSecret123!'),
    ]);
    $this->superAdmin->assignRole('SuperAdmin');
});

test('organization user can view profile edit page', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get(route('admin.profile.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Edit')
        ->where('panel', 'admin')
        ->where('user.name', 'John Doe')
        ->where('user.email', 'john@acme.com')
    );
});

test('superadmin can view superadmin profile edit page', function () {
    $response = $this->actingAs($this->superAdmin)
        ->get(route('superadmin.profile.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Edit')
        ->where('panel', 'superadmin')
        ->where('user.name', 'Super Administrator')
    );
});

test('user can update profile details', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->put(route('admin.profile.update'), [
            'name' => 'Johnathan Doe',
            'email' => 'john.new@acme.com',
            'phone' => '+15551234567',
        ]);

    $response->assertSessionHas('success');

    $this->user->refresh();
    expect($this->user->name)->toBe('Johnathan Doe');
    expect($this->user->email)->toBe('john.new@acme.com');
    expect($this->user->phone)->toBe('+15551234567');
});

test('user can upload and update profile avatar', function () {
    $avatar = UploadedFile::fake()->image('my_avatar.png', 300, 300);

    $response = $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->post(route('admin.profile.avatar.update'), [
            'avatar' => $avatar,
        ]);

    $response->assertSessionHas('success');

    $this->user->refresh();
    expect($this->user->avatar_path)->not->toBeNull();
    expect($this->user->avatar_url)->not->toBeNull();
    Storage::disk('public')->assertExists($this->user->avatar_path);
});

test('user can delete profile avatar', function () {
    $path = 'avatars/sample.jpg';
    Storage::disk('public')->put($path, 'fake-content');
    $this->user->update(['avatar_path' => $path]);

    $response = $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->delete(route('admin.profile.avatar.delete'));

    $response->assertSessionHas('success');

    $this->user->refresh();
    expect($this->user->avatar_path)->toBeNull();
    expect($this->user->avatar_url)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('user can change password with valid current password', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->put(route('admin.profile.password.update'), [
            'current_password' => 'OldPassword123!',
            'password' => 'BrandNewPassword999!',
            'password_confirmation' => 'BrandNewPassword999!',
        ]);

    $response->assertSessionHas('success');

    $this->user->refresh();
    expect(Hash::check('BrandNewPassword999!', $this->user->password))->toBeTrue();
});

test('user cannot change password with incorrect current password', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->put(route('admin.profile.password.update'), [
            'current_password' => 'WrongPassword!',
            'password' => 'BrandNewPassword999!',
            'password_confirmation' => 'BrandNewPassword999!',
        ]);

    $response->assertSessionHasErrors('current_password');

    $this->user->refresh();
    expect(Hash::check('OldPassword123!', $this->user->password))->toBeTrue();
});

test('user can update avatar via media library url', function () {
    $mediaUrl = 'https://cdn.example.com/uploads/user-avatar.webp';

    $response = $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->post(route('admin.profile.avatar.update'), [
            'avatar_url' => $mediaUrl,
        ]);

    $response->assertSessionHas('success');

    $this->user->refresh();
    expect($this->user->avatar_path)->toBe($mediaUrl);
    expect($this->user->avatar_url)->toBe($mediaUrl);
});
