<?php

namespace Modules\Tenancy\Domain\Enums;

enum BranchStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isInactive(): bool
    {
        return $this === self::Inactive;
    }
}
