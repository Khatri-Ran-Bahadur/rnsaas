<?php

namespace Modules\HRM\Domain\Enums;

enum OvertimeType: string
{
    case REGULAR = 'regular';
    case WEEKEND = 'weekend';
    case HOLIDAY = 'holiday';

    public function label(): string
    {
        return match ($this) {
            self::REGULAR => 'Regular Overtime',
            self::WEEKEND => 'Weekend Overtime',
            self::HOLIDAY => 'Holiday Overtime',
        };
    }
}
