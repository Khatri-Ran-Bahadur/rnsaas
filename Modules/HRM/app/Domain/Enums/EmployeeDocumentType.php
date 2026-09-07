<?php

namespace Modules\HRM\Domain\Enums;

enum EmployeeDocumentType: string
{
    case PASSPORT = 'passport';
    case NATIONAL_ID = 'national_id';
    case DRIVING_LICENSE = 'driving_license';
    case EMPLOYMENT_CONTRACT = 'employment_contract';
    case EDUCATION_CERTIFICATE = 'education_certificate';
    case EXPERIENCE_LETTER = 'experience_letter';
    case PROFESSIONAL_CERTIFICATE = 'professional_certificate';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PASSPORT => 'Passport',
            self::NATIONAL_ID => 'National ID',
            self::DRIVING_LICENSE => 'Driving License',
            self::EMPLOYMENT_CONTRACT => 'Employment Contract',
            self::EDUCATION_CERTIFICATE => 'Education Certificate',
            self::EXPERIENCE_LETTER => 'Experience Letter',
            self::PROFESSIONAL_CERTIFICATE => 'Professional Certificate',
            self::OTHER => 'Other',
        };
    }
}
