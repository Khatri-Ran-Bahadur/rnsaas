import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import en from '../locales/en.json';
import ne from '../locales/ne.json';
import hi from '../locales/hi.json';
import ar from '../locales/ar.json';
import es from '../locales/es.json';
import ms from '../locales/ms.json';

type Dictionary = Record<string, any>;

const dictionaries: Record<string, Dictionary> = {
    en,
    ne,
    hi,
    ar,
    es,
    ms,
};

function getNestedValue(obj: Record<string, any>, path: string): string | undefined {
    const parts = path.split('.');
    let current: any = obj;

    for (const part of parts) {
        if (current && typeof current === 'object' && part in current) {
            current = current[part];
        } else {
            return undefined;
        }
    }

    return typeof current === 'string' ? current : undefined;
}

export function translate(
    key: string,
    replacementsOrDefault?: Record<string, string | number> | string,
    defaultVal?: string,
    customLocale?: string
): string {
    let replacements: Record<string, string | number> = {};
    let fallbackText: string | undefined = defaultVal;

    if (typeof replacementsOrDefault === 'string') {
        fallbackText = replacementsOrDefault;
    } else if (replacementsOrDefault && typeof replacementsOrDefault === 'object') {
        replacements = replacementsOrDefault;
    }

    let activeLocale = customLocale;

    if (!activeLocale) {
        try {
            const page = usePage();
            activeLocale =
                (page?.props as any)?.locale ||
                (page?.props?.current_tenant as any)?.locale ||
                (typeof localStorage !== 'undefined' ? localStorage.getItem('app_locale') : null) ||
                'en';
        } catch {
            activeLocale = (typeof localStorage !== 'undefined' ? localStorage.getItem('app_locale') : null) || 'en';
        }
    }

    const dict = (activeLocale && dictionaries[activeLocale]) ? dictionaries[activeLocale] : dictionaries.en;
    let text = getNestedValue(dict, key);

    // Fallback to English if missing in target dictionary
    if (!text && activeLocale !== 'en' && dictionaries.en) {
        text = getNestedValue(dictionaries.en, key);
    }

    if (!text) {
        text = fallbackText ?? key.split('.').pop()?.replace(/_/g, ' ') ?? key;
    }

    // Param substitution: :name or {name}
    if (replacements && Object.keys(replacements).length > 0) {
        for (const [rKey, rVal] of Object.entries(replacements)) {
            text = text.replace(new RegExp(`:${rKey}`, 'g'), String(rVal));
            text = text.replace(new RegExp(`{${rKey}}`, 'g'), String(rVal));
        }
    }

    return text;
}

export function useTranslation() {
    const page = usePage();

    const locale = computed<string>(() => {
        return (
            (page.props as any)?.locale ||
            (page.props?.current_tenant as any)?.locale ||
            (typeof localStorage !== 'undefined' ? localStorage.getItem('app_locale') : null) ||
            'en'
        );
    });

    const t = (
        key: string,
        replacementsOrDefault?: Record<string, string | number> | string,
        defaultVal?: string
    ) => {
        return translate(key, replacementsOrDefault, defaultVal, locale.value);
    };

    return {
        t,
        locale,
        availableLocales: ['en', 'ne', 'hi'],
    };
}
