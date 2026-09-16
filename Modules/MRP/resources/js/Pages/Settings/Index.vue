<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Button, Badge } from '@/components';

interface MrpSettings {
    auto_reserve_materials: boolean;
    allow_negative_raw_materials: boolean;
    require_qa_approval_before_fg: boolean;
    default_scrap_percentage: number;
    mrp_planning_horizon_days: number;
    default_overhead_allocation_rate: number;
    track_lot_genealogy: boolean;
    require_manager_override_for_scrap: boolean;
}

const props = defineProps<{
    settings: MrpSettings;
}>();

const form = useForm({
    auto_reserve_materials: props.settings.auto_reserve_materials ?? true,
    allow_negative_raw_materials: props.settings.allow_negative_raw_materials ?? false,
    require_qa_approval_before_fg: props.settings.require_qa_approval_before_fg ?? true,
    default_scrap_percentage: props.settings.default_scrap_percentage ?? 2.0,
    mrp_planning_horizon_days: props.settings.mrp_planning_horizon_days ?? 30,
    default_overhead_allocation_rate: props.settings.default_overhead_allocation_rate ?? 15.0,
    track_lot_genealogy: props.settings.track_lot_genealogy ?? true,
    require_manager_override_for_scrap: props.settings.require_manager_override_for_scrap ?? true,
});

const submit = () => {
    form.post('/admin/mrp/settings', {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout>
        <Head title="MRP Manufacturing Settings & Parameters" />

        <div class="space-y-6 max-w-4xl mx-auto pb-16">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/mrp" class="hover:text-zinc-700 dark:hover:text-zinc-300">MRP</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Settings</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Manufacturing & Shop Floor Parameters
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Configure production scheduling rules, material reservation logic, and quality gates.
                    </p>
                </div>

                <Button
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
                <!-- Material Reservation & Inventory Gateways -->
                <Card class="p-6 space-y-5">
                    <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Material Control & Reservation</h2>
                        <p class="text-xs text-zinc-500">Define how raw materials are locked and allocated when work orders are planned.</p>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                v-model="form.auto_reserve_materials"
                                type="checkbox"
                                class="mt-1 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <div>
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Automatic Material Reservation</span>
                                <p class="text-xs text-zinc-500">Automatically reserve required BOM component stock when a Work Order is confirmed.</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                v-model="form.allow_negative_raw_materials"
                                type="checkbox"
                                class="mt-1 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <div>
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Allow Negative Raw Material Inventory</span>
                                <p class="text-xs text-zinc-500">Allow shop floor operators to issue raw materials even if recorded stock is zero.</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                v-model="form.track_lot_genealogy"
                                type="checkbox"
                                class="mt-1 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <div>
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Full Batch / Lot Genealogy Traceability</span>
                                <p class="text-xs text-zinc-500">Enforce recording component supplier batch numbers into finished product serial numbers.</p>
                            </div>
                        </label>
                    </div>
                </Card>

                <!-- Quality & Scrap Standards -->
                <Card class="p-6 space-y-5">
                    <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Quality Assurance & Wastage Tolerances</h2>
                        <p class="text-xs text-zinc-500">Control finished goods inspection gates and standard scrap thresholds.</p>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                v-model="form.require_qa_approval_before_fg"
                                type="checkbox"
                                class="mt-1 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <div>
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Mandatory QA Inspection Gate</span>
                                <p class="text-xs text-zinc-500">Prevent moving finished goods to sellable inventory until QA inspection passes.</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                v-model="form.require_manager_override_for_scrap"
                                type="checkbox"
                                class="mt-1 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <div>
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Manager Authorization for Excessive Scrap</span>
                                <p class="text-xs text-zinc-500">Require supervisor pin/approval if recorded scrap exceeds the default scrap percentage.</p>
                            </div>
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Default Standard Scrap Rate (%)
                                </label>
                                <input
                                    v-model.number="form.default_scrap_percentage"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="100"
                                    class="w-full text-sm rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white px-3 py-2"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Overhead Allocation Rate (%)
                                </label>
                                <input
                                    v-model.number="form.default_overhead_allocation_rate"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="100"
                                    class="w-full text-sm rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white px-3 py-2"
                                />
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Scheduling & Horizon -->
                <Card class="p-6 space-y-5">
                    <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">MRP Planning Engine Horizon</h2>
                        <p class="text-xs text-zinc-500">Configure forward forecast and procurement calculation lookahead window.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Planning Horizon Window (Days)
                        </label>
                        <input
                            v-model.number="form.mrp_planning_horizon_days"
                            type="number"
                            min="1"
                            max="365"
                            class="w-full sm:w-64 text-sm rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white px-3 py-2"
                        />
                        <p class="text-xs text-zinc-500 mt-1">Calculates gross requirements and material lead times within this forward window.</p>
                    </div>
                </Card>

                <div class="flex justify-end gap-3">
                    <Button type="button" variant="secondary" size="sm" @click="form.reset()">Reset</Button>
                    <Button type="submit" variant="primary" size="sm" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Configuration' }}
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
