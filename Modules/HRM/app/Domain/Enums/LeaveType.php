<?php

namespace Modules\HRM\Domain\Enums;

enum LeaveType: string
{
    case ANNUAL = 'annual';
    case SICK = 'sick';
    case CASUAL = 'casual';
    case UNPAID = 'unpaid';
    case MATERNITY = 'maternity';
    case PATERNITY = 'paternity';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ANNUAL => 'Annual Leave',
            self::SICK => 'Sick Leave',
            self::CASUAL => 'Casual Leave',
            self::UNPAID => 'Unpaid Leave',
            self::MATERNITY => 'Maternity Leave',
            self::PATERNITY => 'Paternity Leave',
            self::OTHER => 'Other Leave',
        };
    }
}
