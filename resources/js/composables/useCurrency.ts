import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useCurrency() {
    const page = usePage();

    const currentTenant = computed(() => (page.props.current_tenant as any) ?? null);

    const currency = computed<string>(() => {
        return currentTenant.value?.currency || (page.props as any).currency || 'USD';
    });

    const currencySymbol = computed<string>(() => {
        if (currentTenant.value?.currency_symbol) {
            return currentTenant.value.currency_symbol;
        }

        const c = currency.value.toUpperCase();
        switch (c) {
            case 'USD': return '$';
            case 'EUR': return '€';
            case 'GBP': return '£';
            case 'INR': return '₹';
            case 'NPR': return 'रू';
            case 'MYR': return 'RM';
            case 'CAD': return 'CA$';
            case 'AUD': return 'A$';
            case 'SGD': return 'S$';
            case 'AED': return 'AED';
            case 'SAR': return 'SAR';
            case 'QAR': return 'QAR';
            case 'JPY':
            case 'CNY': return '¥';
            default: return c;
        }
    });

    const formatMoney = (val?: number | string | null, withSymbol = true): string => {
        const num = Number(val) || 0;
        const formatted = num.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        if (!withSymbol) {
            return formatted;
        }

        return `${currencySymbol.value} ${formatted}`;
    };

    return {
        currency,
        currencyCode: currency,
        currencySymbol,
        formatMoney,
    };
}
