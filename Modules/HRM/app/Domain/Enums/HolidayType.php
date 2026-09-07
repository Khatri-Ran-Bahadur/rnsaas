<?php

namespace Modules\HRM\Domain\Enums;

enum HolidayType: string
{
    case PUBLIC = 'public';
    case COMPANY = 'company';
    case OPTIONAL = 'optional';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIC => 'Public Holiday',
            self::COMPANY => 'Company Holiday',
            self::OPTIONAL => 'Optional Holiday',
            self::OTHER => 'Other',
        };
    }
}
