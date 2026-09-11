<?php

namespace Modules\Accounting\Domain\Enums;

enum CustomerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
