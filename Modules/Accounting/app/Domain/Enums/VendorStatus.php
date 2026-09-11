<?php

namespace Modules\Accounting\Domain\Enums;

enum VendorStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
