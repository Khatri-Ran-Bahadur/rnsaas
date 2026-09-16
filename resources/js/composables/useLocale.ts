import { computed, watchEffect } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { LOCALE_OPTIONS } from '@/constants/referenceData';

export interface LocaleOption {
    code: string;
    name: string;
    flag: string;
}

export const RTL_LOCALES = ['ar', 'he', 'fa', 'ur'];

export function useLocale() {
    const page = usePage();

    const currentTenant = computed(() => (page.props.current_tenant as any) ?? null);

    const locale = computed<string>(() => {
        return (page.props as any).locale || currentTenant.value?.locale || (typeof localStorage !== 'undefined' ? localStorage.getItem('app_locale') : null) || 'en';
    });

    const isRtl = computed<boolean>(() => {
        return RTL_LOCALES.includes(locale.value.toLowerCase());
    });

    const dir = computed<'rtl' | 'ltr'>(() => {
        return isRtl.value ? 'rtl' : 'ltr';
    });

    // Keep <html> dir and lang attributes in sync reactively
    watchEffect(() => {
        if (typeof document !== 'undefined') {
            document.documentElement.setAttribute('dir', dir.value);
            document.documentElement.setAttribute('lang', locale.value);
        }
    });

    const supportedLocales = computed<LocaleOption[]>(() => {
        const fromProps = (page.props as any).supported_locales;
        if (Array.isArray(fromProps) && fromProps.length > 0) {
            return fromProps;
        }

        return LOCALE_OPTIONS.map((l) => ({
            code: l.value,
            name: l.label,
            flag: l.flag || '🌐',
        }));
    });

    const currentLocaleObj = computed(() => {
        return supportedLocales.value.find((l) => l.code === locale.value) || supportedLocales.value[0];
    });

    const switchLocale = (newLocale: string) => {
        if (typeof localStorage !== 'undefined') {
            localStorage.setItem('app_locale', newLocale);
        }
        if (typeof document !== 'undefined') {
            const isRtlLang = RTL_LOCALES.includes(newLocale.toLowerCase());
            document.documentElement.setAttribute('dir', isRtlLang ? 'rtl' : 'ltr');
            document.documentElement.setAttribute('lang', newLocale);
        }
        router.post(`/locale/${newLocale}`, {}, {
            preserveState: false,
            preserveScroll: true,
        });
    };

    return {
        locale,
        isRtl,
        dir,
        supportedLocales,
        currentLocaleObj,
        switchLocale,
    };
}
