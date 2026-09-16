<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Button } from '@/components';

export interface GroupRow {
    id: number;
    name: string;
    code: string;
    frequency: 'monthly' | 'weekly' | 'biweekly' | 'custom';
    working_days_per_month: number;
    standard_daily_hours: number;
    active_headcount: number;
    currency: string;
    cutoff_attendance_days_before: number;
    cutoff_overtime_days_before: number;
    pay_day_of_month: string;
    overtime_normal_multiplier: number;
    overtime_restday_multiplier: number;
    overtime_holiday_multiplier: number;
    proration_method: string;
    status: string;
}

const props = defineProps<{
    groups: GroupRow[];
}>();

const isModalOpen = ref(false);
const newName = ref('');
const newCode = ref('');
const newFreq = ref('monthly');
const newWorkingDays = ref(22);
const newPayDay = ref('5th of following month');

const handleSaveGroup = () => {
    router.post('/admin/payroll/groups', {
        name: newName.value,
        code: newCode.value,
        frequency: newFreq.value,
        working_days_per_month: newWorkingDays.value,
        pay_day_of_month: newPayDay.value,
    }, {
        onSuccess: () => {
            isModalOpen.value = false;
        }
    });
};
</script>

<template>
    <Head title="Payroll Groups & Pay Calendars - SathiSaaS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/payroll" class="hover:text-emerald-600 transition">Payroll</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Payroll Groups</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Payroll Groups & Pay Calendars</span>
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Configure multi-frequency pay groups (Monthly Corporate, Weekly Retail/Shift, Daily Labor), working day divisors, and cutoff policies.
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <Button variant="primary" size="sm" @click="isModalOpen = true">
                        + New Payroll Group
                    </Button>
                </div>
            </div>

            <!-- Groups Grid Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card
                    v-for="grp in groups"
                    :key="grp.id"
                    class="p-6 space-y-4 flex flex-col justify-between border-t-4 border-emerald-500 hover:shadow-md transition"
                >
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-mono font-bold bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded text-zinc-700 dark:text-zinc-300">
                                {{ grp.code }}
                            </span>
                            <Badge variant="success" class="uppercase text-[9px]">{{ grp.frequency }}</Badge>
                        </div>

                        <div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-white">{{ grp.name }}</h3>
                            <p class="text-xs text-zinc-500">{{ grp.active_headcount }} Active Employees Assigned</p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Pay Disbursement:</span>
                                <strong>{{ grp.pay_day_of_month }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Working Days Base:</span>
                                <strong>{{ grp.working_days_per_month }} days / mo ({{ grp.standard_daily_hours }}h/day)</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500">OT Multipliers:</span>
                                <strong class="font-mono">1.5x (N) / 2.0x (R) / 3.0x (H)</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Proration Formula:</span>
                                <span class="capitalize font-semibold text-emerald-600">{{ grp.proration_method.replace('_', ' ') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-zinc-100 dark:border-zinc-800 text-xs">
                        <span class="text-zinc-400">Cutoff: {{ grp.cutoff_attendance_days_before }} days before</span>
                        <Link
                            :href="`/admin/payroll/runs/create?group_id=${grp.id}`"
                            class="font-bold text-emerald-600 hover:text-emerald-500"
                        >
                            Launch Run →
                        </Link>
                    </div>
                </Card>
            </div>

            <!-- Create Group Modal -->
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
            >
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 max-w-lg w-full space-y-5 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white">Create Payroll Group</h3>
                        <button type="button" @click="isModalOpen = false" class="text-zinc-400 hover:text-zinc-600">✕</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Group Name</label>
                            <input
                                v-model="newName"
                                type="text"
                                placeholder="e.g., Factory Shift Workers (Bi-Weekly)"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Group Code</label>
                                <input
                                    v-model="newCode"
                                    type="text"
                                    placeholder="GRP_FACTORY"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono uppercase"
                                />
                            </div>
                            <div>
                                <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Frequency</label>
                                <select
                                    v-model="newFreq"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl"
                                >
                                    <option value="monthly">Monthly</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="biweekly">Bi-Weekly</option>
                                    <option value="custom">Custom / Milestone</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Standard Working Days Base</label>
                                <input
                                    v-model.number="newWorkingDays"
                                    type="number"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl"
                                />
                            </div>
                            <div>
                                <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Payment Target Day</label>
                                <input
                                    v-model="newPayDay"
                                    type="text"
                                    placeholder="e.g., 28th of every month"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                        <Button variant="outline" size="sm" @click="isModalOpen = false">Cancel</Button>
                        <Button variant="primary" size="sm" @click="handleSaveGroup">Save Group</Button>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
