<?php

namespace Modules\Tenancy\Domain\Contracts;

interface BelongsToTenant
{
    public function getTenantId(): int;
}
