<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge, DataTable, type TableColumn } from '@/components';
import TaxExemptionModal from '../../Components/TaxExemptionModal.vue';
import { usePermissions } from '@/composables/usePermissions';

interface ExemptionItem {
    id: number;
    entity_type: 'customer' | 'vendor';
    entity_name: string;
    certificate_number: string;
    exemption_reason: string;
    effective_from: string;
    effective_until?: string | null;
    status: 'active' | 'expired' | 'pending_review' | 'rejected';
    document_url?: string | null;
    verified_by?: string;
    verified_at?: string;
    notes?: string;
}

const props = defineProps<{
    exemptions: ExemptionItem[];
    filters: {
        search?: string;
        entity_type?: string;
        status?: string;
    };
}>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('tax.manage') || can('tax.manage_exemptions'));

const showModal = ref(false);
const editingExemption = ref<ExemptionItem | null>(null);

const form = useForm({
    entity_type: 'customer' as 'customer' | 'vendor',
    entity_name: '',
    certificate_number: '',
    exemption_reason: '',
    effective_from: new Date().toISOString().slice(0, 10),
    effective_until: '',
    notes: '',
});

const openCreate = () => {
    editingExemption.value = null;
    form.reset();
    showModal.value = true;
};

const openEdit = (item: ExemptionItem) => {
    editingExemption.value = item;
    form.entity_type = item.entity_type;
    form.entity_name = item.entity_name;
    form.certificate_number = item.certificate_number;
    form.exemption_reason = item.exemption_reason;
    form.effective_from = item.effective_from;
    form.effective_until = item.effective_until || '';
    form.notes = item.notes || '';
    showModal.value = true;
};

const handleSave = (data: any) => {
    if (editingExemption.value) {
        form.put(`/admin/tax/exemptions/${editingExemption.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/admin/tax/exemptions', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteExemption = (item: ExemptionItem) => {
    if (confirm(`Are you sure you want to delete certificate "${item.certificate_number}"?`)) {
        router.delete(`/admin/tax/exemptions/${item.id}`);
    }
};

const columns: TableColumn[] = [
    { key: 'certificate', label: 'Certificate # & Entity' },
    { key: 'type', label: 'Party Type' },
    { key: 'reason', label: 'Statutory Exemption Reason' },
    { key: 'validity', label: 'Validity Period' },
    { key: 'status', label: 'Audit Status', align: 'center' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'active':
            return { label: 'Verified Active', class: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200' };
        case 'expired':
            return { label: 'Expired', class: 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200' };
        case 'pending_review':
            return { label: 'Pending Audit', class: 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200' };
        default:
            return { label: status, class: 'bg-zinc-100 text-zinc-700' };
    }
};
</script>

<template>
    <OrganizationLayout>
        <Head title="Tax Exemption Certificates" />

        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/tax" class="hover:text-zinc-700 dark:hover:text-zinc-300">Tax</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Exemptions</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Tax Exemption Certificates & Audits
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Manage official customer export certificates, government ministry exemptions, and pioneer status vendor waivers.
                    </p>
                </div>

                <Button
                    v-if="canManage"
                    variant="primary"
                    size="sm"
                    @click="openCreate"
                >
                    + Register Exemption
                </Button>
            </div>

            <!-- Exemptions Table -->
            <Card class="p-0 overflow-hidden">
                <DataTable
                    :columns="columns"
                    :data="exemptions"
                >
                    <template #cell-certificate="{ row }">
                        <div class="py-2">
                            <div class="font-mono font-bold text-xs text-zinc-900 dark:text-white flex items-center gap-2">
                                <span>{{ row.certificate_number }}</span>
                                <span v-if="row.document_url" class="text-[10px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 dark:bg-blue-950 font-sans">
                                    📎 PDF Attached
                                </span>
                            </div>
                            <div class="text-xs font-medium text-zinc-700 dark:text-zinc-300 mt-0.5">
                                {{ row.entity_name }}
                            </div>
                        </div>
                    </template>

                    <template #cell-type="{ row }">
                        <span class="capitalize px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                            {{ row.entity_type }}
                        </span>
                    </template>

                    <template #cell-reason="{ row }">
                        <div class="text-xs text-zinc-800 dark:text-zinc-200">
                            {{ row.exemption_reason }}
                        </div>
                        <div v-if="row.verified_by" class="text-[10px] text-zinc-400 mt-0.5">
                            Audited by: {{ row.verified_by }} ({{ row.verified_at }})
                        </div>
                    </template>

                    <template #cell-validity="{ row }">
                        <div class="text-xs font-mono text-zinc-600 dark:text-zinc-400">
                            <div>From: {{ row.effective_from }}</div>
                            <div>Until: {{ row.effective_until || 'Indefinite' }}</div>
                        </div>
                    </template>

                    <template #cell-status="{ row }">
                        <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-semibold uppercase border', getStatusBadge(row.status).class]">
                            {{ getStatusBadge(row.status).label }}
                        </span>
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="flex items-center justify-end gap-2">
                            <button
                                v-if="canManage"
                                type="button"
                                @click="openEdit(row)"
                                class="text-xs text-indigo-600 hover:underline"
                            >
                                Edit
                            </button>
                            <button
                                v-if="canManage"
                                type="button"
                                @click="deleteExemption(row)"
                                class="text-xs text-rose-600 hover:underline"
                            >
                                Delete
                            </button>
                        </div>
                    </template>
                </DataTable>
            </Card>

            <TaxExemptionModal
                :show="showModal"
                :editing-item="editingExemption as any"
                @close="showModal = false"
                @save="handleSave"
            />
        </div>
    </OrganizationLayout>
</template>
