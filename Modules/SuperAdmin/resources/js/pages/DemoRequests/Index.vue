<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import Button from '@/components/Button.vue';
import Select from '@/components/Select.vue';
import Modal from '@/components/Modal.vue';

interface DemoItem {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    company_name: string;
    company_size?: string | null;
    message?: string | null;
    status: 'pending' | 'contacted' | 'scheduled' | 'completed';
    notes?: string | null;
    created_at: string;
}

const props = defineProps<{
    requests: {
        data: DemoItem[];
        links: any[];
        total: number;
    };
    filters: {
        status?: string;
    };
}>();

const selectedDemo = ref<DemoItem | null>(null);
const isDetailOpen = ref(false);

const updateForm = useForm({
    status: 'pending',
    notes: '',
});

const openDetail = (item: DemoItem) => {
    selectedDemo.value = item;
    updateForm.status = item.status;
    updateForm.notes = item.notes || '';
    isDetailOpen.value = true;
};

const saveStatus = () => {
    if (!selectedDemo.value) return;

    updateForm.put(`/superadmin/demo-requests/${selectedDemo.value.id}`, {
        onSuccess: () => {
            isDetailOpen.value = false;
        },
    });
};

const deleteDemo = (id: number) => {
    if (confirm('Are you sure you want to remove this lead?')) {
        router.delete(`/superadmin/demo-requests/${id}`);
    }
};

const filterStatus = (status: string) => {
    router.get('/superadmin/demo-requests', { status: status || undefined }, { preserveState: true });
};
</script>

<template>
    <SuperAdminLayout>
        <Head title="Demo Requests - SuperAdmin" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="mb-1 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <span>SuperAdmin</span>
                        <span>/</span>
                        <span class="text-zinc-800 font-medium dark:text-zinc-200">Sales & Inquiries</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Enterprise Demo Requests
                    </h1>
                    <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                        Review and follow up with inbound sales leads and customer demo inquiries.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <select
                        :value="filters.status || ''"
                        @change="filterStatus(($event.target as HTMLSelectElement).value)"
                        class="rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-medium text-zinc-700 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="contacted">Contacted</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="py-3 px-6">Contact / Lead</th>
                                <th class="py-3 px-4">Company</th>
                                <th class="py-3 px-4">Team Size</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4">Submitted</th>
                                <th class="py-3 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 text-zinc-700 dark:divide-zinc-800 dark:text-zinc-300">
                            <tr v-if="requests.data.length === 0">
                                <td colspan="6" class="py-8 text-center text-xs text-zinc-400">
                                    No demo requests found.
                                </td>
                            </tr>
                            <tr
                                v-for="req in requests.data"
                                :key="req.id"
                                class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 transition-colors"
                            >
                                <td class="py-4 px-6">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ req.name }}</div>
                                    <div class="text-xs text-zinc-400">{{ req.email }} <span v-if="req.phone">&bull; {{ req.phone }}</span></div>
                                </td>
                                <td class="py-4 px-4 font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ req.company_name }}
                                </td>
                                <td class="py-4 px-4 text-xs text-zinc-500">
                                    {{ req.company_size || 'Not specified' }}
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span
                                        :class="[
                                            req.status === 'pending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400' :
                                            req.status === 'contacted' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-400' :
                                            req.status === 'scheduled' ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-400' :
                                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400',
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize'
                                        ]"
                                    >
                                        {{ req.status }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-xs text-zinc-500">
                                    {{ new Date(req.created_at).toLocaleDateString() }}
                                </td>
                                <td class="py-4 px-6 text-right space-x-3">
                                    <button
                                        type="button"
                                        @click="openDetail(req)"
                                        class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400 cursor-pointer"
                                    >
                                        View & Manage
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteDemo(req.id)"
                                        class="text-xs font-medium text-rose-600 hover:underline dark:text-rose-400 cursor-pointer"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detail Modal -->
            <Modal :show="isDetailOpen" @close="isDetailOpen = false" max-width="lg">
                <div v-if="selectedDemo" class="p-6 space-y-5">
                    <div class="flex items-start justify-between border-b border-zinc-200 pb-4 dark:border-zinc-800">
                        <div>
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                Demo Request: {{ selectedDemo.company_name }}
                            </h3>
                            <p class="text-xs text-zinc-500">Lead from {{ selectedDemo.name }} ({{ selectedDemo.email }})</p>
                        </div>
                    </div>

                    <div v-if="selectedDemo.message" class="rounded-xl border border-zinc-100 bg-zinc-50 p-4 text-xs text-zinc-700 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300">
                        <strong class="block text-zinc-900 dark:text-zinc-100 mb-1">Customer Inquiry / Requirements:</strong>
                        <p>{{ selectedDemo.message }}</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Pipeline Status
                            </label>
                            <select
                                v-model="updateForm.status"
                                class="flex h-10 w-full items-center rounded-lg border border-zinc-200 bg-white px-3 text-sm text-zinc-900 shadow-2xs dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"
                            >
                                <option value="pending">Pending Follow-up</option>
                                <option value="contacted">Contacted / Emailed</option>
                                <option value="scheduled">Demo Call Scheduled</option>
                                <option value="completed">Demo Completed / Converted</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Internal Notes
                            </label>
                            <textarea
                                v-model="updateForm.notes"
                                rows="3"
                                class="flex w-full rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-900 shadow-2xs dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"
                                placeholder="Add private notes on discussion or scheduled date..."
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                        <Button variant="outline" @click="isDetailOpen = false">Close</Button>
                        <Button :disabled="updateForm.processing" @click="saveStatus">Save Changes</Button>
                    </div>
                </div>
            </Modal>
        </div>
    </SuperAdminLayout>
</template>
