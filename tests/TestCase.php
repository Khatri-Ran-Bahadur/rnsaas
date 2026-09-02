<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--path' => 'Modules/Tenancy/database/migrations']);
        $this->artisan('migrate', ['--path' => 'Modules/Audit/database/migrations']);
    }
}
