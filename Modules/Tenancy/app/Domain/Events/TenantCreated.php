<?php

namespace Modules\Tenancy\Domain\Events;

use Modules\Tenancy\Models\Tenant;

final readonly class TenantCreated
{
    public function __construct(
        public Tenant $tenant,
    ) {}
}
