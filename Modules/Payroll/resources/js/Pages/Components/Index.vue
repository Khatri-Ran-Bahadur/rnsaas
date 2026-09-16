<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Button } from '@/components';

export interface ComponentItem {
    id: number;
    name: string;
    code: string;
    category: string;
    calculation_method: string;
    formula_text?: string;
    default_percentage?: number;
    is_taxable: boolean;
    is_epf_applicable?: boolean;
    is_socso_applicable?: boolean;
    is_eis_applicable?: boolean;
    is_overtime_base?: boolean;
    is_leave_deduction_base?: boolean;
    gl_account_code: string;
    status: string;
}

const props = defineProps<{
    components: ComponentItem[];
    categories: Record<string, string>;
}>();

const isModalOpen = ref(false);
const newName = ref('');
const newCode = ref('');
const newCategory = ref('allowance');
const newMethod = ref('fixed');
const isTaxable = ref(true);
const isEpf = ref(true);
const isSocso = ref(true);
const glCode = ref('5100-02 (Staff Allowances)');

const handleCreateComponent = () => {
    router.post('/admin/payroll/components', {
        name: newName.value,
        code: newCode.value,
        category: newCategory.value,
        calculation_method: newMethod.value,
        is_taxable: isTaxable.value,
        is_epf_applicable: isEpf.value,
        is_socso_applicable: isSocso.value,
        gl_account_code: glCode.value,
    }, {
        onSuccess: () => {
            isModalOpen.value = false;
        }
    });
};
</script>

<template>
    <Head title="Payroll Components Library - SathiSaaS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/payroll" class="hover:text-emerald-600 transition">Payroll</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Payroll Components</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Configurable Payroll Components Library</span>
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Define custom earnings, allowances, statutory deductions, calculation formulas, taxability flags, and GL accounts.
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <Button variant="primary" size="sm" @click="isModalOpen = true">
                        + New Component
                    </Button>
                </div>
            </div>

            <!-- Components Table -->
            <Card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px] tracking-wider">
                            <tr>
                                <th class="px-4 py-3.5">Component Name & Code</th>
                                <th class="px-4 py-3.5">Category</th>
                                <th class="px-4 py-3.5">Calculation Method</th>
                                <th class="px-4 py-3.5 text-center">Taxable</th>
                                <th class="px-4 py-3.5 text-center">EPF</th>
                                <th class="px-4 py-3.5 text-center">SOCSO</th>
                                <th class="px-4 py-3.5">GL Ledger Account</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr
                                v-for="c in components"
                                :key="c.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition"
                            >
                                <td class="px-4 py-3.5">
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ c.name }}</div>
                                    <div class="text-[10px] text-zinc-500 font-mono">{{ c.code }}</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="capitalize font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ categories[c.category] || c.category }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-mono text-zinc-800 dark:text-zinc-200 font-semibold">{{ c.calculation_method }}</div>
                                    <div v-if="c.formula_text" class="text-[10px] text-zinc-500 italic">{{ c.formula_text }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-center font-bold">
                                    <span v-if="c.is_taxable" class="text-emerald-600">✓</span>
                                    <span v-else class="text-zinc-400">—</span>
                                </td>
                                <td class="px-4 py-3.5 text-center font-bold">
                                    <span v-if="c.is_epf_applicable" class="text-emerald-600">✓</span>
                                    <span v-else class="text-zinc-400">—</span>
                                </td>
                                <td class="px-4 py-3.5 text-center font-bold">
                                    <span v-if="c.is_socso_applicable" class="text-emerald-600">✓</span>
                                    <span v-else class="text-zinc-400">—</span>
                                </td>
                                <td class="px-4 py-3.5 text-zinc-600 dark:text-zinc-400 font-mono text-[11px]">
                                    {{ c.gl_account_code }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <Badge variant="success" class="uppercase text-[9px]">{{ c.status }}</Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- Create Component Modal -->
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
            >
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 max-w-lg w-full space-y-5 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white">Register Payroll Component</h3>
                        <button type="button" @click="isModalOpen = false" class="text-zinc-400 hover:text-zinc-600">✕</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Component Name</label>
                            <input
                                v-model="newName"
                                type="text"
                                placeholder="e.g., Remote Work Allowance"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Unique Code</label>
                                <input
                                    v-model="newCode"
                                    type="text"
                                    placeholder="e.g., ALLOW_REMOTE"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono uppercase"
                                />
                            </div>
                            <div>
                                <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Category</label>
                                <select
                                    v-model="newCategory"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl"
                                >
                                    <option v-for="(val, key) in categories" :key="key" :value="key">{{ val }}</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Calculation Method</label>
                            <select
                                v-model="newMethod"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl"
                            >
                                <option value="fixed">Fixed Amount</option>
                                <option value="percentage_basic">% of Basic Salary</option>
                                <option value="percentage_gross">% of Gross Salary</option>
                                <option value="hourly_rate">Hourly Rate Multiplier</option>
                                <option value="formula">Custom Mathematical Formula</option>
                            </select>
                        </div>

                        <div class="flex items-center space-x-6 pt-2">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" v-model="isTaxable" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-semibold text-zinc-700 dark:text-zinc-300">Subject to PCB Tax</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" v-model="isEpf" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-semibold text-zinc-700 dark:text-zinc-300">Subject to EPF</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" v-model="isSocso" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-semibold text-zinc-700 dark:text-zinc-300">Subject to SOCSO</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                        <Button variant="outline" size="sm" @click="isModalOpen = false">Cancel</Button>
                        <Button variant="primary" size="sm" @click="handleCreateComponent">Save Component</Button>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
