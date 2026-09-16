<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge, Modal } from '@/components';
import { usePermissions } from '@/composables/usePermissions';

interface TaxSettings {
    country: string;
    tax_registration_number: string;
    registered_business_name: string;
    tax_authority_name: string;
    tax_regime: string;
    reporting_frequency: string;
    accounting_method: string;
    default_sales_tax_rate_id: number | string | '';
    default_purchase_tax_rate_id: number | string | '';
    default_pricing_mode: 'inclusive' | 'exclusive';
    allow_cashier_tax_override: boolean;
    rounding_level: 'line' | 'tax_group' | 'invoice_total';
    rounding_precision: number;
    rounding_direction: string;
    display_tax_summary_on_invoices: boolean;
    enable_einvoice_compliance: boolean;
}

interface TaxRateOption {
    id: number | string;
    name: string;
    code?: string;
    rate: number;
    category?: string;
}

interface CountryOption {
    code: string;
    name: string;
    regime: string;
}

const props = defineProps<{
    settings: TaxSettings;
    availableRates: TaxRateOption[];
    countries?: CountryOption[];
    tenantCountryCode?: string;
}>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('tax.manage') || can('tax.manage_settings') || true);

const form = useForm({
    country: props.settings.country || props.tenantCountryCode || 'NP',
    tax_registration_number: props.settings.tax_registration_number || '',
    registered_business_name: props.settings.registered_business_name || '',
    tax_authority_name: props.settings.tax_authority_name || '',
    tax_regime: props.settings.tax_regime || 'vat',
    reporting_frequency: props.settings.reporting_frequency || 'monthly',
    accounting_method: props.settings.accounting_method || 'accrual',
    default_sales_tax_rate_id: props.settings.default_sales_tax_rate_id || '',
    default_purchase_tax_rate_id: props.settings.default_purchase_tax_rate_id || '',
    default_pricing_mode: props.settings.default_pricing_mode || 'exclusive',
    allow_cashier_tax_override: Boolean(props.settings.allow_cashier_tax_override),
    rounding_level: props.settings.rounding_level || 'line',
    rounding_precision: props.settings.rounding_precision || 2,
    rounding_direction: props.settings.rounding_direction || 'half_up',
    display_tax_summary_on_invoices: props.settings.display_tax_summary_on_invoices !== false,
    enable_einvoice_compliance: Boolean(props.settings.enable_einvoice_compliance),
});

const defaultCountries: CountryOption[] = [
    { code: 'NP', name: 'Nepal 🇳🇵 (PAN / VAT 13%)', regime: 'vat' },
    { code: 'IN', name: 'India 🇮🇳 (GST 18% / 12% / 5%)', regime: 'gst' },
    { code: 'MY', name: 'Malaysia 🇲🇾 (SST 8% / 6%)', regime: 'sst' },
    { code: 'GB', name: 'United Kingdom 🇬🇧 (VAT 20%)', regime: 'vat' },
    { code: 'US', name: 'United States 🇺🇸 (State Sales Tax)', regime: 'sales_tax' },
    { code: 'SG', name: 'Singapore 🇸🇬 (GST 9%)', regime: 'gst' },
    { code: 'AU', name: 'Australia 🇦🇺 (GST 10%)', regime: 'gst' },
    { code: 'CA', name: 'Canada 🇨🇦 (GST / HST)', regime: 'gst' },
    { code: 'AE', name: 'United Arab Emirates 🇦🇪 (VAT 5%)', regime: 'vat' },
    { code: 'SA', name: 'Saudi Arabia 🇸🇦 (VAT 15%)', regime: 'vat' },
    { code: 'GL', name: 'Other / Global Universal 🌐', regime: 'custom' },
];

const countryList = computed(() => props.countries?.length ? props.countries : defaultCountries);

const onCountryChange = () => {
    const found = countryList.value.find(c => c.code === form.country);
    if (found) {
        form.tax_regime = found.regime;
        if (found.code === 'NP') {
            form.tax_authority_name = 'Inland Revenue Department (IRD / आन्तरिक राजस्व विभाग)';
        } else if (found.code === 'IN') {
            form.tax_authority_name = 'Goods and Services Tax Network (GSTN) / CBIC';
        } else if (found.code === 'MY') {
            form.tax_authority_name = 'Royal Malaysian Customs Department (JKDM)';
        } else if (found.code === 'GB') {
            form.tax_authority_name = 'HM Revenue & Customs (HMRC)';
        }
    }
};

const applyingPreset = ref(false);
const applyCountryPreset = () => {
    if (!confirm(`Are you sure you want to load standard tax rates and defaults for ${form.country}? This will ensure standard VAT/GST/Exempt rates are available.`)) {
        return;
    }
    applyingPreset.value = true;
    router.post('/admin/tax/settings/apply-preset', {
        country_code: form.country,
    }, {
        preserveScroll: true,
        onFinish: () => {
            applyingPreset.value = false;
        },
    });
};

// Modal for quick adding new custom rate
const isRateModalOpen = ref(false);
const newRateForm = useForm({
    name: '',
    code: '',
    rate: 13.00,
    rate_type: 'percentage',
    tax_category: 'standard',
    tax_type_id: 1,
    effective_from: new Date().toISOString().split('T')[0],
    description: '',
});

const submitNewRate = () => {
    newRateForm.post('/admin/tax/rates', {
        preserveScroll: true,
        onSuccess: () => {
            isRateModalOpen.value = false;
            newRateForm.reset();
        },
    });
};

const submit = () => {
    form.put('/admin/tax/settings', {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout>
        <Head title="Business Tax Profile & Rounding Configuration" />

        <div class="space-y-6 max-w-5xl mx-auto pb-16">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/tax" class="hover:text-zinc-700 dark:hover:text-zinc-300">Tax</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Tax Settings</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Company Tax Profile & Rounding Rules
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Configure business statutory identification, filing frequencies, pricing modes, and mathematical rounding rules.
                    </p>
                </div>

                <Button
                    v-if="canManage"
                    type="button"
                    variant="primary"
                    size="sm"
                    :disabled="form.processing"
                    @click="submit"
                >
                    {{ form.processing ? 'Saving...' : 'Save Configuration' }}
                </Button>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- 1. Legal Entity & Tax Registration -->
                <Card class="p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                            1. Statutory Identification & Legal Profile
                        </h3>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-zinc-500">Preset:</span>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="applyingPreset"
                                class="border-emerald-500/50 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 text-xs font-semibold"
                                @click="applyCountryPreset"
                            >
                                <span v-if="applyingPreset">Loading...</span>
                                <span v-else>⚡ Apply {{ form.country }} Standard Tax Rates</span>
                            </Button>
                        </div>
                    </div>

                    <!-- Preset Banner Information -->
                    <div v-if="form.country === 'NP'" class="mb-5 p-3.5 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-900 dark:text-emerald-200 flex items-start gap-2.5">
                        <span class="text-base leading-none">🇳🇵</span>
                        <div class="space-y-0.5">
                            <p class="font-bold">Nepal Statutory Tax Profile (IRD - आन्तरिक राजस्व विभाग):</p>
                            <p class="text-[11px] text-emerald-800/90 dark:text-emerald-300/90">
                                Standard Rate: <strong>13% VAT</strong> | Non-Taxable / Exempt: <strong>0% VAT</strong> (Schedule 1 basic foods, medicines, books) | Export: <strong>0% Zero-Rated</strong> | TDS: <strong>1.5% Contract, 10% Services/Rent</strong>.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Country / Jurisdiction <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.country"
                                required
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                                @change="onCountryChange"
                            >
                                <option v-for="c in countryList" :key="c.code" :value="c.code">
                                    {{ c.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Registered Business Legal Name <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.registered_business_name"
                                type="text"
                                required
                                placeholder="e.g. My Business / Organization Name"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                {{ form.country === 'NP' ? 'PAN / VAT Registration Number (स्थायी लेखा नम्बर / मूल्य अभिवृद्धि कर दर्ता)' : 'Tax Registration Number (PAN / VAT / GST / Tax ID)' }}
                            </label>
                            <input
                                v-model="form.tax_registration_number"
                                type="text"
                                :placeholder="form.country === 'NP' ? 'e.g. 601234567 (9-digit PAN)' : 'e.g. PAN 102938475 or VAT 987654321'"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Tax Authority Name
                            </label>
                            <input
                                v-model="form.tax_authority_name"
                                type="text"
                                :placeholder="form.country === 'NP' ? 'Inland Revenue Department (IRD / आन्तरिक राजस्व विभाग)' : 'e.g. IRS, GST Department, HMRC'"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Primary Tax System / Regime
                            </label>
                            <select
                                v-model="form.tax_regime"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            >
                                <option value="vat">VAT (Value Added Tax - e.g. Nepal 13%, UK 20%, EU)</option>
                                <option value="pan_tds">PAN / TDS / Withholding Tax System</option>
                                <option value="gst">GST (Goods & Services Tax - e.g. India, Australia, Singapore)</option>
                                <option value="sales_tax">Sales Tax (Single-stage / State Tax)</option>
                                <option value="custom">Custom / Flexible Multi-Tax Rate System</option>
                            </select>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                You can configure custom percentage rates (e.g. 13%, 14%, 0%) under Tax Rates regardless of the selected regime.
                            </p>
                        </div>
                    </div>
                </Card>

                <!-- 2. Accounting & Reporting Method -->
                <Card class="p-6">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                        2. Reporting Frequency & Accounting Method
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Statutory Return Filing Frequency
                            </label>
                            <select
                                v-model="form.reporting_frequency"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            >
                                <option value="monthly">Monthly (12 returns / year)</option>
                                <option value="bimonthly">Bi-Monthly (Every 2 months)</option>
                                <option value="quarterly">Quarterly (4 returns / year)</option>
                                <option value="semi_annual">Semi-Annually (2 returns / year)</option>
                                <option value="annual">Annually (1 return / year)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Tax Accounting Recognition Method
                            </label>
                            <select
                                v-model="form.accounting_method"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            >
                                <option value="accrual">Accrual Basis (Tax recognized upon invoice/bill issuance)</option>
                                <option value="cash">Cash Basis (Tax recognized upon receipt/payment of cash)</option>
                            </select>
                        </div>
                    </div>
                </Card>

                <!-- 3. Defaults & Price Quotation -->
                <Card class="p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                            3. Default Tax Rates & Catalog Mode
                        </h3>
                        <div class="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="text-xs"
                                @click="isRateModalOpen = true"
                            >
                                + Add Custom Rate (%)
                            </Button>
                            <Link
                                href="/admin/tax/rates"
                                class="text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 font-medium underline"
                            >
                                Manage All Rates & Dates &rarr;
                            </Link>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Default Sales Tax Rate
                            </label>
                            <select
                                v-model="form.default_sales_tax_rate_id"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            >
                                <option value="">Select default sales tax...</option>
                                <option v-for="r in availableRates" :key="r.id" :value="r.id">
                                    {{ r.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Default Purchase Tax Rate
                            </label>
                            <select
                                v-model="form.default_purchase_tax_rate_id"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            >
                                <option value="">Select default purchase tax...</option>
                                <option v-for="r in availableRates" :key="r.id" :value="r.id">
                                    {{ r.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Default Price Quotation Mode
                            </label>
                            <select
                                v-model="form.default_pricing_mode"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            >
                                <option value="exclusive">Tax-Exclusive (Tax added at checkout)</option>
                                <option value="inclusive">Tax-Inclusive (Catalog prices include tax)</option>
                            </select>
                        </div>
                    </div>
                </Card>

                <!-- 4. Rounding Rules & Precision -->
                <Card class="p-6">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                        4. Tax Rounding Policy & Mathematical Precision
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Rounding Aggregation Level
                            </label>
                            <select
                                v-model="form.rounding_level"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            >
                                <option value="line">Per Line Item (Round each invoice row)</option>
                                <option value="tax_group">Per Tax Rate Subtotal (Grouped by rate code)</option>
                                <option value="invoice_total">Invoice Grand Total Level</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Rounding Direction Method
                            </label>
                            <select
                                v-model="form.rounding_direction"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            >
                                <option value="half_up">Standard Half-Up Arithmetic (>= 0.5 rounds up)</option>
                                <option value="floor">Floor / Truncate (Always round down)</option>
                                <option value="ceil">Ceil (Always round up)</option>
                                <option value="round_to_5_cents">Nearest 5 Cents / Nickel Rounding (POS cash)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Tax Decimal Precision
                            </label>
                            <select
                                v-model.number="form.rounding_precision"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white font-mono"
                            >
                                <option :value="2">2 Decimal Places (Standard e.g. 0.08)</option>
                                <option :value="3">3 Decimal Places (e.g. 0.085)</option>
                                <option :value="4">4 Decimal Places (High Precision e.g. 0.0855)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Extra compliance checkboxes -->
                    <div class="space-y-3 mt-6 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                        <label class="flex items-center gap-2.5 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.display_tax_summary_on_invoices"
                                class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                            />
                            <span>Print itemized Tax Summary breakdown box on PDF Invoices and Receipts</span>
                        </label>

                        <label class="flex items-center gap-2.5 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.enable_einvoice_compliance"
                                class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                            />
                            <span>Enable statutory e-Invoice validation & QR code embedding</span>
                        </label>
                    </div>
                </Card>
            </form>

            <!-- Quick Add Custom Tax Rate Modal -->
            <Modal
                :show="isRateModalOpen"
                title="Create / Add Custom Tax Rate (%)"
                @close="isRateModalOpen = false"
            >
                <form @submit.prevent="submitNewRate" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                            Tax Rate Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="newRateForm.name"
                            type="text"
                            placeholder="e.g. Standard VAT (14%), Luxury Surcharge (5%), Zero Rate (0%)"
                            required
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Rate Code <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="newRateForm.code"
                                type="text"
                                placeholder="e.g. VAT-14, EX-0"
                                required
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Tax Percentage (%) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model.number="newRateForm.rate"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="14.00"
                                required
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white font-mono"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Tax Category
                            </label>
                            <select
                                v-model="newRateForm.tax_category"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            >
                                <option value="standard">Standard Rate</option>
                                <option value="reduced">Reduced Rate</option>
                                <option value="exempt">Exempt (0% - कर छुट)</option>
                                <option value="zero_rated">Zero-Rated (0% - निकासी/निर्यात)</option>
                                <option value="withholding">Withholding / TDS (अग्रिम कर)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Effective From Date
                            </label>
                            <input
                                v-model="newRateForm.effective_from"
                                type="date"
                                required
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white font-mono"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                            Notes / Government Circular Details
                        </label>
                        <textarea
                            v-model="newRateForm.description"
                            rows="2"
                            placeholder="e.g. Effective under IRD circular for FY 2083/84"
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <Button type="button" variant="outline" size="sm" @click="isRateModalOpen = false">
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" size="sm" :disabled="newRateForm.processing">
                            {{ newRateForm.processing ? 'Saving...' : 'Save Rate' }}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    </OrganizationLayout>
</template>
