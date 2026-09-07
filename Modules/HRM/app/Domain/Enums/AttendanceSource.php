<?php

namespace Modules\HRM\Domain\Enums;

enum AttendanceSource: string
{
    case MANUAL = 'manual';
    case WEB = 'web';
    case MOBILE = 'mobile';
    case BIOMETRIC = 'biometric';
    case IMPORT = 'import';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::WEB => 'Web',
            self::MOBILE => 'Mobile',
            self::BIOMETRIC => 'Biometric',
            self::IMPORT => 'Import',
        };
    }
}
