<?php

namespace App\Support;

class ReferenceData
{
    /**
     * @var array<string, array{name: string, currency: string, timezone: string, locale: string, flag?: string}>|null
     */
    protected static ?array $countriesCache = null;

    /**
     * Return all world countries keyed by ISO-3166-1 alpha-2 code.
     *
     * @return array<string, array{name: string, currency: string, timezone: string, locale: string, flag?: string}>
     */
    public static function countries(): array
    {
        if (static::$countriesCache !== null) {
            return static::$countriesCache;
        }

        $path = resource_path('js/data/countries.json');
        if (file_exists($path)) {
            $raw = json_decode((string) file_get_contents($path), true);
            if (is_array($raw)) {
                $map = [];
                foreach ($raw as $item) {
                    if (is_array($item) && ! empty($item['code'])) {
                        $map[$item['code']] = [
                            'name' => $item['name'] ?? $item['code'],
                            'flag' => $item['flag'] ?? '🌐',
                            'currency' => $item['currency'] ?? 'USD',
                            'timezone' => $item['timezone'] ?? 'UTC',
                            'locale' => $item['locale'] ?? 'en',
                        ];
                    }
                }
                static::$countriesCache = $map;

                return static::$countriesCache;
            }
        }

        static::$countriesCache = [
            'MY' => ['name' => 'Malaysia', 'flag' => '🇲🇾', 'currency' => 'MYR', 'timezone' => 'Asia/Kuala_Lumpur', 'locale' => 'ms'],
            'NP' => ['name' => 'Nepal', 'flag' => '🇳🇵', 'currency' => 'NPR', 'timezone' => 'Asia/Kathmandu', 'locale' => 'ne'],
            'SG' => ['name' => 'Singapore', 'flag' => '🇸🇬', 'currency' => 'SGD', 'timezone' => 'Asia/Singapore', 'locale' => 'en'],
            'US' => ['name' => 'United States', 'flag' => '🇺🇸', 'currency' => 'USD', 'timezone' => 'America/New_York', 'locale' => 'en'],
            'GB' => ['name' => 'United Kingdom', 'flag' => '🇬🇧', 'currency' => 'GBP', 'timezone' => 'Europe/London', 'locale' => 'en'],
            'IN' => ['name' => 'India', 'flag' => '🇮🇳', 'currency' => 'INR', 'timezone' => 'Asia/Kolkata', 'locale' => 'hi'],
            'AU' => ['name' => 'Australia', 'flag' => '🇦🇺', 'currency' => 'AUD', 'timezone' => 'Australia/Sydney', 'locale' => 'en'],
            'CA' => ['name' => 'Canada', 'flag' => '🇨🇦', 'currency' => 'CAD', 'timezone' => 'America/Toronto', 'locale' => 'en'],
            'AE' => ['name' => 'United Arab Emirates', 'flag' => '🇦🇪', 'currency' => 'AED', 'timezone' => 'Asia/Dubai', 'locale' => 'ar'],
            'DE' => ['name' => 'Germany', 'flag' => '🇩🇪', 'currency' => 'EUR', 'timezone' => 'Europe/Berlin', 'locale' => 'de'],
        ];

        return static::$countriesCache;
    }

    /**
     * Get all ISO-3166-1 alpha-2 country codes.
     *
     * @return array<int, string>
     */
    public static function countryCodes(): array
    {
        return array_keys(static::countries());
    }

    /**
     * All world currencies extracted and sorted.
     *
     * @return array<int, string>
     */
    public static function currencies(): array
    {
        $currencies = [];
        foreach (static::countries() as $country) {
            if (! empty($country['currency'])) {
                $currencies[$country['currency']] = true;
            }
        }

        $list = array_keys($currencies);
        sort($list);

        return $list;
    }

    /**
     * Standard timezones from all countries plus common UTC.
     *
     * @return array<int, string>
     */
    public static function timezones(): array
    {
        $tzs = [];
        foreach (static::countries() as $country) {
            if (! empty($country['timezone'])) {
                $tzs[$country['timezone']] = true;
            }
        }
        $tzs['UTC'] = true;

        $list = array_keys($tzs);
        sort($list);

        return $list;
    }

    /**
     * Supported world locales for language switcher and UI localization.
     *
     * @return array<int, array{code: string, name: string, flag: string}>
     */
    public static function locales(): array
    {
        return [
            ['code' => 'en', 'name' => 'English', 'flag' => '🇺🇸'],
            ['code' => 'ne', 'name' => 'नेपाली (Nepali)', 'flag' => '🇳🇵'],
            ['code' => 'hi', 'name' => 'हिन्दी (Hindi)', 'flag' => '🇮🇳'],
            ['code' => 'ms', 'name' => 'Bahasa Melayu', 'flag' => '🇲🇾'],
            ['code' => 'ar', 'name' => 'العربية (Arabic)', 'flag' => '🇸🇦'],
            ['code' => 'es', 'name' => 'Español (Spanish)', 'flag' => '🇪🇸'],
            ['code' => 'fr', 'name' => 'Français (French)', 'flag' => '🇫🇷'],
            ['code' => 'de', 'name' => 'Deutsch (German)', 'flag' => '🇩🇪'],
            ['code' => 'zh', 'name' => '中文 (Chinese)', 'flag' => '🇨🇳'],
            ['code' => 'ja', 'name' => '日本語 (Japanese)', 'flag' => '🇯🇵'],
            ['code' => 'ko', 'name' => '한국어 (Korean)', 'flag' => '🇰🇷'],
            ['code' => 'pt', 'name' => 'Português (Portuguese)', 'flag' => '🇵🇹'],
            ['code' => 'pt-BR', 'name' => 'Português do Brasil', 'flag' => '🇧🇷'],
            ['code' => 'ru', 'name' => 'Русский (Russian)', 'flag' => '🇷🇺'],
            ['code' => 'it', 'name' => 'Italiano (Italian)', 'flag' => '🇮🇹'],
            ['code' => 'nl', 'name' => 'Nederlands (Dutch)', 'flag' => '🇳🇱'],
            ['code' => 'tr', 'name' => 'Türkçe (Turkish)', 'flag' => '🇹🇷'],
            ['code' => 'th', 'name' => 'ไทย (Thai)', 'flag' => '🇹🇭'],
            ['code' => 'vi', 'name' => 'Tiếng Việt (Vietnamese)', 'flag' => '🇻🇳'],
            ['code' => 'id', 'name' => 'Bahasa Indonesia', 'flag' => '🇮🇩'],
            ['code' => 'bn', 'name' => 'বাংলা (Bengali)', 'flag' => '🇧🇩'],
            ['code' => 'ur', 'name' => 'اردو (Urdu)', 'flag' => '🇵🇰'],
            ['code' => 'fa', 'name' => 'فارسی (Persian)', 'flag' => '🇮🇷'],
            ['code' => 'pl', 'name' => 'Polski (Polish)', 'flag' => '🇵🇱'],
            ['code' => 'uk', 'name' => 'Українська (Ukrainian)', 'flag' => '🇺🇦'],
            ['code' => 'he', 'name' => 'עברית (Hebrew)', 'flag' => '🇮🇱'],
            ['code' => 'da', 'name' => 'Dansk (Danish)', 'flag' => '🇩🇰'],
            ['code' => 'sv', 'name' => 'Svenska (Swedish)', 'flag' => '🇸🇪'],
            ['code' => 'no', 'name' => 'Norsk (Norwegian)', 'flag' => '🇳🇴'],
            ['code' => 'fi', 'name' => 'Suomi (Finnish)', 'flag' => '🇫🇮'],
            ['code' => 'el', 'name' => 'Ελληνικά (Greek)', 'flag' => '🇬🇷'],
            ['code' => 'cs', 'name' => 'Čeština (Czech)', 'flag' => '🇨🇿'],
            ['code' => 'ro', 'name' => 'Română (Romanian)', 'flag' => '🇷🇴'],
            ['code' => 'hu', 'name' => 'Magyar (Hungarian)', 'flag' => '🇭🇺'],
            ['code' => 'sw', 'name' => 'Kiswahili (Swahili)', 'flag' => '🇰🇪'],
            ['code' => 'ta', 'name' => 'தமிழ் (Tamil)', 'flag' => '🇮🇳'],
            ['code' => 'te', 'name' => 'తెలుగు (Telugu)', 'flag' => '🇮🇳'],
            ['code' => 'fil', 'name' => 'Filipino / Tagalog', 'flag' => '🇵🇭'],
        ];
    }
}
