<?php

namespace App\Support;

class ReferenceData
{
    /**
     * Common world countries with ISO-3166-1 alpha-2 codes.
     */
    public static function countries(): array
    {
        return [
            'MY' => ['name' => 'Malaysia', 'currency' => 'MYR', 'timezone' => 'Asia/Kuala_Lumpur', 'locale' => 'en'],
            'NP' => ['name' => 'Nepal', 'currency' => 'NPR', 'timezone' => 'Asia/Kathmandu', 'locale' => 'en'],
            'SG' => ['name' => 'Singapore', 'currency' => 'SGD', 'timezone' => 'Asia/Singapore', 'locale' => 'en'],
            'US' => ['name' => 'United States', 'currency' => 'USD', 'timezone' => 'America/New_York', 'locale' => 'en'],
            'GB' => ['name' => 'United Kingdom', 'currency' => 'GBP', 'timezone' => 'Europe/London', 'locale' => 'en'],
            'AU' => ['name' => 'Australia', 'currency' => 'AUD', 'timezone' => 'Australia/Sydney', 'locale' => 'en'],
            'IN' => ['name' => 'India', 'currency' => 'INR', 'timezone' => 'Asia/Kolkata', 'locale' => 'en'],
            'CA' => ['name' => 'Canada', 'currency' => 'CAD', 'timezone' => 'America/Toronto', 'locale' => 'en'],
            'AE' => ['name' => 'United Arab Emirates', 'currency' => 'AED', 'timezone' => 'Asia/Dubai', 'locale' => 'en'],
            'DE' => ['name' => 'Germany', 'currency' => 'EUR', 'timezone' => 'Europe/Berlin', 'locale' => 'de'],
            'FR' => ['name' => 'France', 'currency' => 'EUR', 'timezone' => 'Europe/Paris', 'locale' => 'fr'],
            'JP' => ['name' => 'Japan', 'currency' => 'JPY', 'timezone' => 'Asia/Tokyo', 'locale' => 'ja'],
            'CN' => ['name' => 'China', 'currency' => 'CNY', 'timezone' => 'Asia/Shanghai', 'locale' => 'zh'],
            'ID' => ['name' => 'Indonesia', 'currency' => 'IDR', 'timezone' => 'Asia/Jakarta', 'locale' => 'id'],
            'TH' => ['name' => 'Thailand', 'currency' => 'THB', 'timezone' => 'Asia/Bangkok', 'locale' => 'th'],
            'PH' => ['name' => 'Philippines', 'currency' => 'PHP', 'timezone' => 'Asia/Manila', 'locale' => 'en'],
            'VN' => ['name' => 'Vietnam', 'currency' => 'VND', 'timezone' => 'Asia/Ho_Chi_Minh', 'locale' => 'vi'],
            'NZ' => ['name' => 'New Zealand', 'currency' => 'NZD', 'timezone' => 'Pacific/Auckland', 'locale' => 'en'],
            'SA' => ['name' => 'Saudi Arabia', 'currency' => 'SAR', 'timezone' => 'Asia/Riyadh', 'locale' => 'ar'],
            'QA' => ['name' => 'Qatar', 'currency' => 'QAR', 'timezone' => 'Asia/Qatar', 'locale' => 'ar'],
        ];
    }

    /**
     * Common world currencies.
     */
    public static function currencies(): array
    {
        return ['MYR', 'USD', 'EUR', 'GBP', 'SGD', 'AUD', 'CAD', 'JPY', 'INR', 'NPR', 'AED', 'SAR', 'QAR', 'CNY', 'HKD', 'CHF', 'IDR', 'THB', 'PHP', 'VND', 'KRW', 'NZD', 'ZAR', 'BRL', 'MXN', 'TRY'];
    }

    /**
     * Standard common timezones.
     */
    public static function timezones(): array
    {
        return [
            'Asia/Kuala_Lumpur',
            'Asia/Kathmandu',
            'Asia/Singapore',
            'Asia/Kolkata',
            'Asia/Dubai',
            'Asia/Riyadh',
            'Asia/Bangkok',
            'Asia/Jakarta',
            'Asia/Tokyo',
            'UTC',
            'Europe/London',
            'Europe/Paris',
            'Europe/Berlin',
            'America/New_York',
            'America/Chicago',
            'America/Los_Angeles',
            'America/Toronto',
            'Australia/Sydney',
        ];
    }
}
