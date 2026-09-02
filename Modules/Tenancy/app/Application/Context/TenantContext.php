<?php

namespace Modules\Tenancy\Application\Context;

use LogicException;
use Modules\Tenancy\Models\Tenant;

final class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }

    public function get(): Tenant
    {
        if ($this->tenant === null) {
            throw new LogicException('No tenant has been resolved for the current request.');
        }

        return $this->tenant;
    }

    public function id(): int
    {
        return $this->get()->getKey();
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }
}
