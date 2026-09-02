<?php

namespace Modules\Tenancy\Tests\Unit\Application\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Application\Actions\CreateTenantAction;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Tests\TestCase;

class CreateTenantActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_tenant(): void
    {
        $data = new CreateTenantData(
            name: 'ABC Restaurant',
            slug: 'abc-restaurant',
            industry: 'restaurant',
            countryCode: 'MY',
            timezone: 'Asia/Kuala_Lumpur',
            locale: 'en',
            currency: 'MYR',
            status: TenantStatus::Pending,
        );

        $tenant = app(CreateTenantAction::class)->execute($data);

        $this->assertInstanceOf(Tenant::class, $tenant);

        $this->assertDatabaseHas('tenants', [
            'name' => 'ABC Restaurant',
            'slug' => 'abc-restaurant',
            'industry' => 'restaurant',
            'country_code' => 'MY',
            'currency' => 'MYR',
        ]);

        $this->assertNotEmpty($tenant->public_id);
    }
}
