<?php

namespace Modules\Tenancy\Domain\Enums;

enum DesignationStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
