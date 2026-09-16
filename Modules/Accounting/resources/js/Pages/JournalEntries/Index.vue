<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Badge, Button, DataTable, Select, type TableColumn } from '@/components';
import type { PaginatedData } from '@/types/tenancy';
import { usePermissions } from '@/composables/usePermissions';
import { create, index, post } from '@/routes/admin/accounting/journal-entries';

interface JournalEntry {
    id: string;
    entry_number: string;
    entry_date: string;
    description: string;
    status: { value: 'draft' | 'posted' | 'reversed'; label: string };
    debit_total: string;
    credit_total: string;
}

const props = defineProps<{
    journals: PaginatedData<JournalEntry>;
    filters: { status?: string };
}>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('accounting.manage'));
const selectedStatus = ref(props.filters.status ?? '');
const columns: TableColumn[] = [
    { key: 'entry_number', label: 'Entry #' },
    { key: 'entry_date', label: 'Date' },
    { key: 'description', label: 'Description' },
    { key: 'debit_total', label: 'Debit', align: 'right' },
    { key: 'credit_total', label: 'Credit', align: 'right' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const formatAmount = (amount: string) => new Intl.NumberFormat('en', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
}).format(Number(amount));

const visit = (page = 1) => router.get(index.url({ query: {
    page,
    status: selectedStatus.value || undefined,
} }), {}, { preserveScroll: true, preserveState: true });

const postJournal = (journal: JournalEntry) => {
    router.post(post.url(journal.id), {}, { preserveScroll: true });
};
</script>

<template>
    <OrganizationLayout
        title="Journal Entries"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Accounting', href: index.url() },
            { label: 'Journal Entries' },
        ]"
    >
        <Head title="Journal Entries - Accounting" />

        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Journal Entries</h1>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Record balanced adjustments before posting them to the general ledger.</p>
                </div>
                <Link v-if="canManage" :href="create.url()"><Button>New Journal Entry</Button></Link>
            </div>

            <DataTable
                :columns="columns"
                :data="journals"
                :searchable="false"
                empty-title="No journal entries found"
                empty-description="Create a balanced debit and credit entry for an accounting adjustment."
                @page-change="visit"
            >
                <template #filters>
                    <div class="w-44">
                        <Select v-model="selectedStatus" :options="[
                            { label: 'All statuses', value: '' },
                            { label: 'Draft', value: 'draft' },
                            { label: 'Posted', value: 'posted' },
                            { label: 'Reversed', value: 'reversed' },
                        ]" @update:model-value="visit()" />
                    </div>
                </template>
                <template #actions><Link v-if="canManage" :href="create.url()"><Button size="sm">New entry</Button></Link></template>
                <template #empty-actions><Link v-if="canManage" :href="create.url()"><Button size="sm">Create first entry</Button></Link></template>
                <template #cell(entry_number)="{ item }"><span class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">{{ item.entry_number }}</span></template>
                <template #cell(entry_date)="{ item }"><span class="font-medium text-zinc-700 dark:text-zinc-300">{{ item.entry_date }}</span></template>
                <template #cell(description)="{ item }"><span class="max-w-sm truncate text-zinc-600 dark:text-zinc-400">{{ item.description }}</span></template>
                <template #cell(debit_total)="{ item }"><span class="font-mono font-semibold">{{ formatAmount(item.debit_total) }}</span></template>
                <template #cell(credit_total)="{ item }"><span class="font-mono font-semibold">{{ formatAmount(item.credit_total) }}</span></template>
                <template #cell(status)="{ item }"><Badge :variant="item.status.value === 'posted' ? 'active' : item.status.value === 'draft' ? 'pending' : 'cancelled'">{{ item.status.label }}</Badge></template>
                <template #cell(actions)="{ item }">
                    <Button v-if="canManage && item.status.value === 'draft'" size="sm" variant="secondary" @click="postJournal(item)">Post</Button>
                </template>
            </DataTable>
        </div>
    </OrganizationLayout>
</template>
