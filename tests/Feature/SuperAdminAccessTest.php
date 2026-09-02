<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('dashboard.view', 'web');

    Role::findOrCreate('SuperAdmin', 'web');
});

test('guest is redirected to login', function () {
    $response = $this->get('/admin/dashboard');

    $response
        ->assertRedirect('/login');
});

test('normal user cannot access superadmin area', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/admin/dashboard');

    $response->assertForbidden();
});

test('superadmin can access superadmin area', function () {
    $user = User::factory()->create();

    $user->assignRole('SuperAdmin');

    $response = $this
        ->actingAs($user)
        ->get('/admin/dashboard');

    $response->assertOk();
});
