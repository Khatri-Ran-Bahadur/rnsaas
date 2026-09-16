<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Button } from '@/components';
import { useCurrency } from '@/composables/useCurrency';

export interface SchemeItem {
    id: number;
    code: string;
    name: string;
    country: string;
    authority: string;
    employee_rate_default: string;
    employer_rate_default: string;
    wage_ceiling: string;
    filing_frequency: string;
    status: string;
}

export interface ReliefItem {
    name: string;
    code: string;
    annual_cap: number;
}

const props = defineProps<{
    country: string;
    schemes: SchemeItem[];
    reliefs: ReliefItem[];
    supportedCountries: Record<string, string>;
}>();

const { currencySymbol } = useCurrency();

const formatMoney = (val: number) => {
    return currencySymbol.value + ' ' + (Number(val) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// Modal state for adding a new scheme
const isModalOpen = ref(false);
const formName = ref('');
const formCode = ref('');
const formCountry = ref(props.country || 'GLOBAL');
const formAuthority = ref('');
const formEmployeeRate = ref<number>(0);
const formEmployerRate = ref<number>(0);
const formWageCeiling = ref<number | null>(null);

const handleToggleStatus = (scheme: SchemeItem) => {
    router.put(`/admin/payroll/statutory/${scheme.id}`, {
        is_active: scheme.status !== 'active',
    }, {
        preserveScroll: true,
    });
};

const handleCreateScheme = () => {
    if (!formName.value.trim()) return;

    router.post('/admin/payroll/statutory', {
        name: formName.value,
        code: formCode.value || formName.value.toUpperCase().replace(/\s+/g, '_'),
        country_code: formCountry.value,
        authority: formAuthority.value || 'National Regulatory Body',
        employee_rate: formEmployeeRate.value,
        employer_rate: formEmployerRate.value,
        wage_ceiling: formWageCeiling.value,
    }, {
        onSuccess: () => {
            isModalOpen.value = false;
            formName.value = '';
            formCode.value = '';
            formAuthority.value = '';
            formEmployeeRate.value = 0;
            formEmployerRate.value = 0;
            formWageCeiling.value = null;
        }
    });
};
</script>

<template>
    <Head title="Statutory Schemes & Tax Compliance - SathiSaaS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/payroll" class="hover:text-emerald-600 transition">Payroll</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Statutory Schemes</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Statutory Schemes & Tax Compliance</span>
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Configure universal and jurisdiction-specific social security, pension funds, health insurance, and progressive payroll tax withholding rules.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Button
                        variant="primary"
                        class="text-xs font-semibold px-4 py-2"
                        @click="isModalOpen = true"
                    >
                        + Add Statutory Scheme
                    </Button>
                </div>
            </div>

            <!-- Statutory Schemes Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Card
                    v-for="s in schemes"
                    :key="s.id"
                    class="p-6 space-y-4 border-l-4 border-emerald-500 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono font-bold text-emerald-700 bg-emerald-50 dark:bg-emerald-950/70 dark:text-emerald-400 px-2 py-0.5 rounded-md border border-emerald-200 dark:border-emerald-800">
                            {{ s.code }}
                        </span>
                        <div class="flex items-center gap-2">
                            <Badge
                                :variant="s.status === 'active' ? 'success' : 'secondary'"
                                class="uppercase text-[9px]"
                            >
                                {{ s.status }}
                            </Badge>
                            <button
                                type="button"
                                class="text-[11px] text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 underline cursor-pointer"
                                @click="handleToggleStatus(s)"
                            >
                                {{ s.status === 'active' ? 'Deactivate' : 'Activate' }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white">{{ s.name }}</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ s.authority }} • {{ s.country }}</p>
                    </div>

                    <div class="space-y-2 p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 text-xs">
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Employee Contribution:</span>
                            <strong class="text-zinc-900 dark:text-white font-semibold">{{ s.employee_rate_default }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Employer Contribution:</span>
                            <strong class="text-zinc-900 dark:text-white font-semibold">{{ s.employer_rate_default }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Statutory Wage Cap:</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ s.wage_ceiling }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Remittance Frequency:</span>
                            <span class="font-medium text-purple-600 dark:text-purple-400">{{ s.filing_frequency }}</span>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Global Tax Deductions & Annual Exemptions Schedule -->
            <Card class="p-6 space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Statutory Tax Deductions & Annual Exemptions</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Standard annual allowable deductions and tax exemptions applied during annual tax assessments and monthly progressive calculations.
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="(rel, idx) in reliefs"
                        :key="idx"
                        class="p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 text-xs space-y-1 hover:border-zinc-300 dark:hover:border-zinc-700 transition"
                    >
                        <div class="font-bold text-zinc-900 dark:text-white">{{ rel.name }}</div>
                        <div class="text-[11px] text-zinc-500 font-mono">Code: {{ rel.code }}</div>
                        <div class="font-mono font-bold text-emerald-600 dark:text-emerald-400 pt-1">
                            Cap: {{ formatMoney(rel.annual_cap) }} / year
                        </div>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Add Statutory Scheme Modal -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs"
        >
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-zinc-900 p-6 shadow-2xl border border-zinc-200 dark:border-zinc-800 space-y-5">
                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Add Statutory Scheme</h3>
                    <button
                        type="button"
                        class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 text-lg leading-none"
                        @click="isModalOpen = false"
                    >
                        ×
                    </button>
                </div>

                <div class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Scheme Name *</label>
                        <input
                            v-model="formName"
                            type="text"
                            placeholder="e.g. National Social Security / Medicare / PF"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-transparent px-3 py-2 text-zinc-900 dark:text-white focus:border-emerald-500 focus:outline-hidden"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Scheme Code</label>
                            <input
                                v-model="formCode"
                                type="text"
                                placeholder="e.g. SOC_SEC"
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-transparent px-3 py-2 text-zinc-900 dark:text-white focus:border-emerald-500 focus:outline-hidden font-mono uppercase"
                            />
                        </div>
                        <div>
                            <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Country</label>
                            <input
                                v-model="formCountry"
                                type="text"
                                placeholder="e.g. GLOBAL / US / UK / NP"
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-transparent px-3 py-2 text-zinc-900 dark:text-white focus:border-emerald-500 focus:outline-hidden uppercase"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Regulating Authority</label>
                        <input
                            v-model="formAuthority"
                            type="text"
                            placeholder="e.g. Social Security Administration / Tax Revenue"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-transparent px-3 py-2 text-zinc-900 dark:text-white focus:border-emerald-500 focus:outline-hidden"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Employee Rate (%)</label>
                            <input
                                v-model.number="formEmployeeRate"
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-transparent px-3 py-2 text-zinc-900 dark:text-white focus:border-emerald-500 focus:outline-hidden"
                            />
                        </div>
                        <div>
                            <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Employer Rate (%)</label>
                            <input
                                v-model.number="formEmployerRate"
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-transparent px-3 py-2 text-zinc-900 dark:text-white focus:border-emerald-500 focus:outline-hidden"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Statutory Wage Cap / Ceiling (Optional)</label>
                        <input
                            v-model.number="formWageCeiling"
                            type="number"
                            step="0.01"
                            placeholder="Leave blank if no ceiling applies"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-transparent px-3 py-2 text-zinc-900 dark:text-white focus:border-emerald-500 focus:outline-hidden"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <Button
                        variant="secondary"
                        class="text-xs px-4 py-2"
                        @click="isModalOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="primary"
                        class="text-xs font-semibold px-4 py-2"
                        @click="handleCreateScheme"
                    >
                        Save Scheme
                    </Button>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
