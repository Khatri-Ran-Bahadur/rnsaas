import rawCountries from '@/data/countries.json';
import type { ComboboxOption } from '@/components/Combobox.vue';

export interface CountryData {
    code: string;
    name: string;
    flag: string;
    currency: string;
    timezone: string;
    locale: string;
}

export const COUNTRIES_DATA: CountryData[] = rawCountries as CountryData[];

export const COUNTRY_OPTIONS: ComboboxOption[] = COUNTRIES_DATA.map((c) => ({
    label: `${c.name} (${c.code})`,
    value: c.code,
    flag: c.flag,
}));

export const CURRENCY_OPTIONS: ComboboxOption[] = [
    { label: 'USD - US Dollar ($)', value: 'USD', flag: '🇺🇸' },
    { label: 'EUR - Euro (€)', value: 'EUR', flag: '🇪🇺' },
    { label: 'GBP - British Pound (£)', value: 'GBP', flag: '🇬🇧' },
    { label: 'NPR - Nepalese Rupee (Rs)', value: 'NPR', flag: '🇳🇵' },
    { label: 'INR - Indian Rupee (₹)', value: 'INR', flag: '🇮🇳' },
    { label: 'MYR - Malaysian Ringgit (RM)', value: 'MYR', flag: '🇲🇾' },
    { label: 'SGD - Singapore Dollar (S$)', value: 'SGD', flag: '🇸🇬' },
    { label: 'AED - UAE Dirham (د.إ)', value: 'AED', flag: '🇦🇪' },
    { label: 'SAR - Saudi Riyal (﷼)', value: 'SAR', flag: '🇸🇦' },
    { label: 'QAR - Qatari Riyal (﷼)', value: 'QAR', flag: '🇶🇦' },
    { label: 'AUD - Australian Dollar (A$)', value: 'AUD', flag: '🇦🇺' },
    { label: 'CAD - Canadian Dollar (C$)', value: 'CAD', flag: '🇨🇦' },
    { label: 'JPY - Japanese Yen (¥)', value: 'JPY', flag: '🇯🇵' },
    { label: 'CNY - Chinese Yuan (¥)', value: 'CNY', flag: '🇨🇳' },
    { label: 'HKD - Hong Kong Dollar (HK$)', value: 'HKD', flag: '🇭🇰' },
    { label: 'CHF - Swiss Franc (CHF)', value: 'CHF', flag: '🇨🇭' },
    { label: 'IDR - Indonesian Rupiah (Rp)', value: 'IDR', flag: '🇮🇩' },
    { label: 'THB - Thai Baht (฿)', value: 'THB', flag: '🇹🇭' },
    { label: 'PHP - Philippine Peso (₱)', value: 'PHP', flag: '🇵🇭' },
    { label: 'VND - Vietnamese Dong (₫)', value: 'VND', flag: '🇻🇳' },
    { label: 'KRW - South Korean Won (₩)', value: 'KRW', flag: '🇰🇷' },
    { label: 'NZD - New Zealand Dollar (NZ$)', value: 'NZD', flag: '🇳🇿' },
    { label: 'ZAR - South African Rand (R)', value: 'ZAR', flag: '🇿🇦' },
    { label: 'BRL - Brazilian Real (R$)', value: 'BRL', flag: '🇧🇷' },
    { label: 'MXN - Mexican Peso (Mex$)', value: 'MXN', flag: '🇲🇽' },
    { label: 'TRY - Turkish Lira (₺)', value: 'TRY', flag: '🇹🇷' },
    { label: 'SEK - Swedish Krona (kr)', value: 'SEK', flag: '🇸🇪' },
    { label: 'NOK - Norwegian Krone (kr)', value: 'NOK', flag: '🇳🇴' },
    { label: 'DKK - Danish Krone (kr)', value: 'DKK', flag: '🇩🇰' },
    { label: 'PLN - Polish Zloty (zł)', value: 'PLN', flag: '🇵🇱' },
    { label: 'BDT - Bangladeshi Taka (৳)', value: 'BDT', flag: '🇧🇩' },
    { label: 'PKR - Pakistani Rupee (Rs)', value: 'PKR', flag: '🇵🇰' },
    { label: 'LKR - Sri Lankan Rupee (Rs)', value: 'LKR', flag: '🇱🇰' },
];

export const TIMEZONE_OPTIONS: ComboboxOption[] = [
    { label: 'Asia/Kuala_Lumpur (UTC+08:00)', value: 'Asia/Kuala_Lumpur' },
    { label: 'Asia/Kathmandu (UTC+05:45)', value: 'Asia/Kathmandu' },
    { label: 'Asia/Singapore (UTC+08:00)', value: 'Asia/Singapore' },
    { label: 'Asia/Kolkata (UTC+05:30)', value: 'Asia/Kolkata' },
    { label: 'Asia/Dubai (UTC+04:00)', value: 'Asia/Dubai' },
    { label: 'Asia/Riyadh (UTC+03:00)', value: 'Asia/Riyadh' },
    { label: 'Asia/Bangkok (UTC+07:00)', value: 'Asia/Bangkok' },
    { label: 'Asia/Jakarta (UTC+07:00)', value: 'Asia/Jakarta' },
    { label: 'Asia/Tokyo (UTC+09:00)', value: 'Asia/Tokyo' },
    { label: 'Asia/Seoul (UTC+09:00)', value: 'Asia/Seoul' },
    { label: 'Asia/Hong_Kong (UTC+08:00)', value: 'Asia/Hong_Kong' },
    { label: 'Asia/Shanghai (UTC+08:00)', value: 'Asia/Shanghai' },
    { label: 'Asia/Dhaka (UTC+06:00)', value: 'Asia/Dhaka' },
    { label: 'Asia/Karachi (UTC+05:00)', value: 'Asia/Karachi' },
    { label: 'Asia/Colombo (UTC+05:30)', value: 'Asia/Colombo' },
    { label: 'UTC (UTC+00:00)', value: 'UTC' },
    { label: 'Europe/London (UTC+00:00)', value: 'Europe/London' },
    { label: 'Europe/Paris (UTC+01:00)', value: 'Europe/Paris' },
    { label: 'Europe/Berlin (UTC+01:00)', value: 'Europe/Berlin' },
    { label: 'Europe/Rome (UTC+01:00)', value: 'Europe/Rome' },
    { label: 'Europe/Madrid (UTC+01:00)', value: 'Europe/Madrid' },
    { label: 'Europe/Amsterdam (UTC+01:00)', value: 'Europe/Amsterdam' },
    { label: 'Europe/Zurich (UTC+01:00)', value: 'Europe/Zurich' },
    { label: 'Europe/Istanbul (UTC+03:00)', value: 'Europe/Istanbul' },
    { label: 'America/New_York (UTC-05:00)', value: 'America/New_York' },
    { label: 'America/Chicago (UTC-06:00)', value: 'America/Chicago' },
    { label: 'America/Denver (UTC-07:00)', value: 'America/Denver' },
    { label: 'America/Los_Angeles (UTC-08:00)', value: 'America/Los_Angeles' },
    { label: 'America/Toronto (UTC-05:00)', value: 'America/Toronto' },
    { label: 'America/Sao_Paulo (UTC-03:00)', value: 'America/Sao_Paulo' },
    { label: 'Australia/Sydney (UTC+10:00)', value: 'Australia/Sydney' },
    { label: 'Australia/Perth (UTC+08:00)', value: 'Australia/Perth' },
    { label: 'Pacific/Auckland (UTC+12:00)', value: 'Pacific/Auckland' },
];

export const LOCALE_OPTIONS: ComboboxOption[] = [
    { label: 'English', value: 'en', flag: '🇬🇧' },
    { label: 'Español', value: 'es', flag: '🇪🇸' },
    { label: 'العربية', value: 'ar', flag: '🇸🇦' },
    { label: 'Dansk', value: 'da', flag: '🇩🇰' },
    { label: 'Deutsch', value: 'de', flag: '🇩🇪' },
    { label: 'Français', value: 'fr', flag: '🇫🇷' },
    { label: 'עברית', value: 'he', flag: '🇮🇱' },
    { label: 'Italiano', value: 'it', flag: '🇮🇹' },
    { label: '日本語', value: 'ja', flag: '🇯🇵' },
    { label: 'Nederlands', value: 'nl', flag: '🇳🇱' },
    { label: 'Polski', value: 'pl', flag: '🇵🇱' },
    { label: 'Português', value: 'pt', flag: '🇵🇹' },
    { label: 'Português do Brasil', value: 'pt-BR', flag: '🇧🇷' },
    { label: 'Русский', value: 'ru', flag: '🇷🇺' },
    { label: 'Türkçe', value: 'tr', flag: '🇹🇷' },
    { label: '中文', value: 'zh', flag: '🇨🇳' },
    { label: 'Bahasa Melayu', value: 'ms', flag: '🇲🇾' },
    { label: 'नेपाली (Nepali)', value: 'ne', flag: '🇳🇵' },
    { label: 'हिन्दी (Hindi)', value: 'hi', flag: '🇮🇳' },
];

export const findCountryDefaults = (countryCode: string): CountryData | undefined => {
    return COUNTRIES_DATA.find((c) => c.code === countryCode);
};
