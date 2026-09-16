<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->artisan('migrate', ['--path' => 'Modules/Tenancy/database/migrations']);
        $this->artisan('migrate', ['--path' => 'Modules/Audit/database/migrations']);
        $this->artisan('migrate', ['--path' => 'Modules/Subscription/database/migrations']);
        $this->artisan('migrate', ['--path' => 'Modules/Payment/database/migrations']);

        if (! file_exists(storage_path('installed'))) {
            file_put_contents(storage_path('installed'), now()->toIso8601String());
        }
    }
}
