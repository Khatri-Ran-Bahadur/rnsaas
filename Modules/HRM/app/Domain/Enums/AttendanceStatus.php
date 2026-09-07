<?php

namespace Modules\HRM\Domain\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LATE = 'late';
    case HALF_DAY = 'half_day';
    case ON_LEAVE = 'on_leave';
    case HOLIDAY = 'holiday';
    case WEEK_OFF = 'week_off';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Present',
            self::ABSENT => 'Absent',
            self::LATE => 'Late',
            self::HALF_DAY => 'Half Day',
            self::ON_LEAVE => 'On Leave',
            self::HOLIDAY => 'Holiday',
            self::WEEK_OFF => 'Week Off',
        };
    }
}
