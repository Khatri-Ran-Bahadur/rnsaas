<script setup lang="ts">
import { ref, computed } from 'vue';
import { useCurrency } from '@/composables/useCurrency';
import { getTaxTerminology } from '@/utils/taxTerminology';
import { usePage } from '@inertiajs/vue3';

const props = defineProps<{
    show: boolean;
    rates?: Array<{
        id: number;
        name: string;
        code: string;
        rate: number;
        rate_type?: string;
    }>;
    initialRate?: number;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const { currencyCode, currencySymbol, formatMoney } = useCurrency();
const page = usePage();
const tenant = computed(() => (page.props as any).current_tenant || {});
const terms = computed(() => getTaxTerminology(tenant.value.country_code, tenant.value.tax_regime));

const testAmount = ref<number>(1000);
const selectedRateId = ref<number | 'custom'>('custom');
const customRatePercent = ref<number>(props.initialRate ?? 13);
const isInclusive = ref<boolean>(false);
const transactionType = ref<'sales' | 'purchase'>('sales');

const activePercent = computed(() => {
    if (selectedRateId.value === 'custom') {
        return Number(customRatePercent.value) || 0;
    }
    const found = props.rates?.find(r => r.id === selectedRateId.value);
    return found ? Number(found.rate) : 0;
});

const calculation = computed(() => {
    const amt = Number(testAmount.value) || 0;
    const r = activePercent.value;

    if (amt <= 0 || r <= 0) {
        return {
            netBase: amt,
            taxAmount: 0,
            grossTotal: amt,
            effectiveRate: 0,
        };
    }

    if (isInclusive.value) {
        const net = amt / (1 + (r / 100));
        const tax = amt - net;
        return {
            netBase: Math.round(net * 100) / 100,
            taxAmount: Math.round(tax * 100) / 100,
            grossTotal: amt,
            effectiveRate: r,
        };
    } else {
        const tax = amt * (r / 100);
        const gross = amt + tax;
        return {
            netBase: amt,
            taxAmount: Math.round(tax * 100) / 100,
            grossTotal: Math.round(gross * 100) / 100,
            effectiveRate: r,
        };
    }
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="relative w-full max-w-2xl rounded-2xl bg-white dark:bg-zinc-900 shadow-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between bg-zinc-50/50 dark:bg-zinc-900/50">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        Universal Tax Calculation & Accounting Simulator
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Simulate gross/net tax breakdowns & double-entry GL ledger impact for {{ tenant.name || 'Tenant' }}
                    </p>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-5">
                <!-- Transaction Context -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5 uppercase tracking-wider">
                            Transaction Stream
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="transactionType = 'sales'"
                                :class="[
                                    'py-2 px-3 text-xs font-medium rounded-lg border transition-all text-center',
                                    transactionType === 'sales'
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                        : 'border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800'
                                ]"
                            >
                                Sales ({{ terms.outputTaxLabel }})
                            </button>
                            <button
                                type="button"
                                @click="transactionType = 'purchase'"
                                :class="[
                                    'py-2 px-3 text-xs font-medium rounded-lg border transition-all text-center',
                                    transactionType === 'purchase'
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                        : 'border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800'
                                ]"
                            >
                                Purchase ({{ terms.inputTaxLabel }})
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5 uppercase tracking-wider">
                            Tax Pricing Treatment
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="isInclusive = false"
                                :class="[
                                    'py-2 px-3 text-xs font-medium rounded-lg border transition-all text-center',
                                    !isInclusive
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                        : 'border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800'
                                ]"
                            >
                                Exclusive (Net + Tax)
                            </button>
                            <button
                                type="button"
                                @click="isInclusive = true"
                                :class="[
                                    'py-2 px-3 text-xs font-medium rounded-lg border transition-all text-center',
                                    isInclusive
                                        ? 'bg-purple-600 text-white border-purple-600 shadow-sm'
                                        : 'border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800'
                                ]"
                            >
                                Inclusive (Gross contains Tax)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Input Amount & Tax Rate -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Input Amount ({{ currencyCode }})
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400 font-mono text-xs">
                                {{ currencySymbol }}
                            </span>
                            <input
                                v-model.number="testAmount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full pl-9 pr-3 py-2 text-sm bg-white dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Tax Rate Applicability
                        </label>
                        <div class="flex gap-2">
                            <select
                                v-if="rates && rates.length > 0"
                                v-model="selectedRateId"
                                class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            >
                                <option value="custom">Custom Rate</option>
                                <option v-for="r in rates" :key="r.id" :value="r.id">
                                    {{ r.name }} ({{ r.rate }}%)
                                </option>
                            </select>
                            <div v-if="selectedRateId === 'custom'" class="relative w-28">
                                <input
                                    v-model.number="customRatePercent"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="100"
                                    placeholder="%"
                                    class="w-full pr-7 pl-3 py-2 text-sm bg-white dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:text-white"
                                />
                                <span class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-zinc-400 text-xs">
                                    %
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Simulation Breakdown Cards -->
                <div class="grid grid-cols-3 gap-3 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800">
                    <div>
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500">Taxable Net Base</span>
                        <div class="text-base font-bold font-mono text-zinc-900 dark:text-zinc-100 mt-0.5">
                            {{ formatMoney(calculation.netBase) }}
                        </div>
                        <span class="text-[10px] text-zinc-400">
                            {{ isInclusive ? 'Tax extracted' : 'Direct line amount' }}
                        </span>
                    </div>

                    <div>
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                            {{ transactionType === 'sales' ? terms.outputTaxLabel : terms.inputTaxLabel }} ({{ activePercent }}%)
                        </span>
                        <div class="text-base font-bold font-mono text-amber-600 dark:text-amber-400 mt-0.5">
                            {{ formatMoney(calculation.taxAmount) }}
                        </div>
                        <span class="text-[10px] text-zinc-400">
                            {{ terms.regimeName }} Component
                        </span>
                    </div>

                    <div>
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Total Settlement</span>
                        <div class="text-base font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">
                            {{ formatMoney(calculation.grossTotal) }}
                        </div>
                        <span class="text-[10px] text-zinc-400">Final Transaction Total</span>
                    </div>
                </div>

                <!-- Double-Entry GL Ledger Impact Preview -->
                <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/50">
                    <div class="flex items-center justify-between mb-2.5">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                            Double-Entry GL Ledger Preview
                        </span>
                        <span class="text-[10px] px-2 py-0.5 rounded font-mono bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                            Balanced Journal
                        </span>
                    </div>

                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 font-medium">
                                <th class="text-left pb-1.5">Account / Leg</th>
                                <th class="text-right pb-1.5">Debit (Dr)</th>
                                <th class="text-right pb-1.5">Credit (Cr)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60 font-mono">
                            <template v-if="transactionType === 'sales'">
                                <tr>
                                    <td class="py-1.5 text-zinc-800 dark:text-zinc-200">
                                        Accounts Receivable / Cash
                                    </td>
                                    <td class="text-right py-1.5 text-emerald-600 dark:text-emerald-400 font-bold">
                                        {{ formatMoney(calculation.grossTotal) }}
                                    </td>
                                    <td class="text-right py-1.5 text-zinc-400">-</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-zinc-800 dark:text-zinc-200 pl-4">
                                        ↳ Sales Revenue
                                    </td>
                                    <td class="text-right py-1.5 text-zinc-400">-</td>
                                    <td class="text-right py-1.5 text-zinc-900 dark:text-white">
                                        {{ formatMoney(calculation.netBase) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-zinc-800 dark:text-zinc-200 pl-4">
                                        ↳ {{ terms.outputTaxLabel }} (Liability)
                                    </td>
                                    <td class="text-right py-1.5 text-zinc-400">-</td>
                                    <td class="text-right py-1.5 text-amber-600 dark:text-amber-400 font-bold">
                                        {{ formatMoney(calculation.taxAmount) }}
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <tr>
                                    <td class="py-1.5 text-zinc-800 dark:text-zinc-200">
                                        Expense / Inventory Asset
                                    </td>
                                    <td class="text-right py-1.5 text-zinc-900 dark:text-white">
                                        {{ formatMoney(calculation.netBase) }}
                                    </td>
                                    <td class="text-right py-1.5 text-zinc-400">-</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-zinc-800 dark:text-zinc-200">
                                        {{ terms.inputTaxLabel }} (Asset/Recovery)
                                    </td>
                                    <td class="text-right py-1.5 text-amber-600 dark:text-amber-400 font-bold">
                                        {{ formatMoney(calculation.taxAmount) }}
                                    </td>
                                    <td class="text-right py-1.5 text-zinc-400">-</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-zinc-800 dark:text-zinc-200 pl-4">
                                        ↳ Accounts Payable / Cash
                                    </td>
                                    <td class="text-right py-1.5 text-zinc-400">-</td>
                                    <td class="text-right py-1.5 text-emerald-600 dark:text-emerald-400 font-bold">
                                        {{ formatMoney(calculation.grossTotal) }}
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-3.5 bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 flex justify-end">
                <button
                    type="button"
                    @click="emit('close')"
                    class="px-4 py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors"
                >
                    Done
                </button>
            </div>
        </div>
    </div>
</template>
