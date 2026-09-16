<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Button } from '@/components';
import { getTaxTerminology } from '@/utils/taxTerminology';
import { useCurrency } from '@/composables/useCurrency';
import TaxPreviewModal from '../../Components/TaxPreviewModal.vue';

interface AccountOption {
    id: number;
    code: string;
    name: string;
    label: string;
    section: string;
}

interface TaxRateOption {
    id: number;
    name: string;
    code: string;
    rate: number;
    rate_type?: string;
    timeline_status: string;
}

const props = defineProps<{
    settings: {
        output_tax_account_id: number | null;
        input_tax_account_id: number | null;
        withholding_tax_account_id: number | null;
        tax_settlement_account_id: number | null;
        tax_regime: string;
        country: string;
    };
    accounts: AccountOption[];
    taxRates: TaxRateOption[];
    taxTypes: Array<{ id: number; name: string; code: string }>;
}>();

const page = usePage();
const tenant = computed(() => (page.props as any).current_tenant || {});
const terms = computed(() => getTaxTerminology(tenant.value.country_code || props.settings.country, props.settings.tax_regime));
const { currencyCode } = useCurrency();

const form = useForm({
    output_tax_account_id: props.settings.output_tax_account_id,
    input_tax_account_id: props.settings.input_tax_account_id,
    withholding_tax_account_id: props.settings.withholding_tax_account_id,
    tax_settlement_account_id: props.settings.tax_settlement_account_id,
});

const showPreview = ref(false);

const isFullyConfigured = computed(() => {
    return form.output_tax_account_id && form.input_tax_account_id;
});

const submit = () => {
    form.put(route('admin.tax.accounts.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Tax Accounting & GL Accounts Mapping" />

    <OrganizationLayout>
        <div class="max-w-6xl mx-auto space-y-6 pb-12">
            <!-- Breadcrumbs / Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white flex items-center gap-3">
                        <span>Tax Chart of Accounts (GL) Mapping</span>
                        <Badge
                            :variant="isFullyConfigured ? 'success' : 'warning'"
                            class="text-xs uppercase"
                        >
                            {{ isFullyConfigured ? 'Fully Integrated' : 'Configuration Required' }}
                        </Badge>
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                        Connect {{ terms.regimeName }} ({{ terms.taxLabel }}) transaction streams directly to General Ledger accounts for automated double-entry postings.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        @click="showPreview = true"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition shadow-sm"
                    >
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Simulate Tax & GL
                    </button>
                    <a
                        :href="route('admin.tax.settings.index')"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition shadow-sm"
                    >
                        Tax Settings
                    </a>
                </div>
            </div>

            <!-- Country & Regime Context banner -->
            <div class="p-4 rounded-xl bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-950/40 dark:to-blue-950/40 border border-indigo-100 dark:border-indigo-900/60 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm">
                        {{ tenant.country_code || 'GL' }}
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">
                            Active Regime: {{ terms.defaultRegimeName || 'Value Added Tax (VAT 13%)' }} ({{ (settings.tax_regime || 'VAT').toUpperCase() }})
                        </div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                            Country/Jurisdiction: {{ settings.country || 'Nepal' }} &bull; Currency: {{ currencyCode }} &bull; Tax Authority: {{ terms.authorityPlaceholder || 'Inland Revenue Department (IRD)' }}
                        </div>
                    </div>
                </div>

                <span class="text-xs px-2.5 py-1 rounded-md bg-white/80 dark:bg-zinc-800/80 border border-indigo-200 dark:border-indigo-800 font-mono text-indigo-700 dark:text-indigo-300">
                    Double-Entry GAAP Compliant
                </span>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Account Mappings Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 1. Output Tax Account -->
                    <Card class="p-5 space-y-4 border-l-4 border-l-amber-500">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">
                                    {{ terms.outputTaxLabel }} Account (Liability)
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Credited automatically when sales invoices or POS receipts are posted.
                                </p>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                Sales Stream
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                Chart of Accounts GL Target
                            </label>
                            <select
                                v-model="form.output_tax_account_id"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            >
                                <option :value="null">-- Select Output Tax GL Account --</option>
                                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                    {{ acc.label }} ({{ acc.section }})
                                </option>
                            </select>
                            <p v-if="form.errors.output_tax_account_id" class="text-xs text-red-500 mt-1">
                                {{ form.errors.output_tax_account_id }}
                            </p>
                        </div>
                    </Card>

                    <!-- 2. Input Tax Account -->
                    <Card class="p-5 space-y-4 border-l-4 border-l-emerald-500">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">
                                    {{ terms.inputTaxLabel }} Account (Asset / Recovery)
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Debited automatically when vendor purchase bills are posted with recoverable tax.
                                </p>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                Purchase Stream
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                Chart of Accounts GL Target
                            </label>
                            <select
                                v-model="form.input_tax_account_id"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            >
                                <option :value="null">-- Select Input Tax GL Account --</option>
                                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                    {{ acc.label }} ({{ acc.section }})
                                </option>
                            </select>
                            <p v-if="form.errors.input_tax_account_id" class="text-xs text-red-500 mt-1">
                                {{ form.errors.input_tax_account_id }}
                            </p>
                        </div>
                    </Card>

                    <!-- 3. Withholding Tax Account -->
                    <Card class="p-5 space-y-4 border-l-4 border-l-blue-500">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">
                                    {{ terms.withholdingTaxLabel }} Account (Liability)
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Withheld on supplier payments or consultant payouts for tax authority remittance.
                                </p>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                Withholding / TDS
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                Chart of Accounts GL Target
                            </label>
                            <select
                                v-model="form.withholding_tax_account_id"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            >
                                <option :value="null">-- Select Withholding Tax Account --</option>
                                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                    {{ acc.label }} ({{ acc.section }})
                                </option>
                            </select>
                            <p v-if="form.errors.withholding_tax_account_id" class="text-xs text-red-500 mt-1">
                                {{ form.errors.withholding_tax_account_id }}
                            </p>
                        </div>
                    </Card>

                    <!-- 4. Tax Settlement Account -->
                    <Card class="p-5 space-y-4 border-l-4 border-l-purple-500">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">
                                    Tax Clearing / Settlement Account
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Used for monthly/quarterly tax return offset (Net Output Tax minus Input Tax).
                                </p>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-800">
                                Periodic Filing
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                Chart of Accounts GL Target
                            </label>
                            <select
                                v-model="form.tax_settlement_account_id"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            >
                                <option :value="null">-- Select Tax Settlement Account --</option>
                                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                    {{ acc.label }} ({{ acc.section }})
                                </option>
                            </select>
                            <p v-if="form.errors.tax_settlement_account_id" class="text-xs text-red-500 mt-1">
                                {{ form.errors.tax_settlement_account_id }}
                            </p>
                        </div>
                    </Card>
                </div>

                <!-- Submit Action -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6"
                    >
                        <span v-if="form.processing">Saving Mappings...</span>
                        <span v-else>Save GL Account Mappings</span>
                    </Button>
                </div>
            </form>

            <!-- Configured Tax Rates Reference Table -->
            <Card class="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                            Configured Tax Rates for {{ settings.country }}
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Active tax rates available across Invoicing, Purchase Bills, POS, and Item catalog.
                        </p>
                    </div>
                    <a
                        :href="route('admin.tax.rates.index')"
                        class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                    >
                        Manage Tax Rates &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 font-medium">
                                <th class="text-left pb-2">Code</th>
                                <th class="text-left pb-2">Rate Name</th>
                                <th class="text-right pb-2">Percentage / Rate</th>
                                <th class="text-center pb-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                            <tr v-for="rate in taxRates" :key="rate.id" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                                <td class="py-2.5 font-mono font-bold text-zinc-800 dark:text-zinc-200">
                                    {{ rate.code }}
                                </td>
                                <td class="py-2.5 text-zinc-900 dark:text-white font-medium">
                                    {{ rate.name }}
                                </td>
                                <td class="py-2.5 text-right font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ rate.rate }}%
                                </td>
                                <td class="py-2.5 text-center">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                                            rate.timeline_status === 'active'
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                                : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                        ]"
                                    >
                                        {{ rate.timeline_status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!taxRates || taxRates.length === 0">
                                <td colspan="4" class="py-6 text-center text-zinc-400">
                                    No tax rates configured yet. Configure rates in Tax Rates module.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>

        <TaxPreviewModal
            :show="showPreview"
            :rates="taxRates"
            @close="showPreview = false"
        />
    </OrganizationLayout>
</template>
