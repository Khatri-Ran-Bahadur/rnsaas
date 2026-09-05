<?php

namespace App\Support\Tenancy;

use Modules\Tenancy\Models\Tenant;
use RuntimeException;

final class CurrentTenant
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
            throw new RuntimeException('No current tenant has been resolved.');
        }

        return $this->tenant;
    }

    public function id(): int
    {
        return $this->get()->id;
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }
}
