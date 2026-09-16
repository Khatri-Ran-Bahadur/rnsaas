<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, DatePicker, Select, type SelectOption } from '@/components';
import { index, store } from '@/routes/admin/accounting/journal-entries';

interface JournalLine { account_id: number | ''; line_type: 'debit' | 'credit'; amount: string; description: string }
const props = defineProps<{ accounts: { id: number; code: string; name: string }[] }>();
const form = useForm({
    entry_number: `JE-${new Date().getFullYear()}-${String(Date.now()).slice(-6)}`,
    entry_date: new Date().toISOString().split('T')[0],
    description: '',
    lines: [
        { account_id: '' as number | '', line_type: 'debit' as const, amount: '', description: '' },
        { account_id: '' as number | '', line_type: 'credit' as const, amount: '', description: '' },
    ] as JournalLine[],
});
const accountOptions = computed<SelectOption[]>(() => props.accounts.map((account) => ({ label: `${account.code} — ${account.name}`, value: account.id })));
const total = (type: JournalLine['line_type']) => form.lines.filter((line) => line.line_type === type).reduce((sum, line) => sum + (Number(line.amount) || 0), 0);
const debitTotal = computed(() => total('debit'));
const creditTotal = computed(() => total('credit'));
const isBalanced = computed(() => debitTotal.value > 0 && debitTotal.value === creditTotal.value);
const addLine = (lineType: JournalLine['line_type']) => form.lines.push({ account_id: '', line_type: lineType, amount: '', description: '' });
const removeLine = (lineIndex: number) => { if (form.lines.length > 2) form.lines.splice(lineIndex, 1); };
const submit = () => form.post(store.url(), { preserveScroll: true });
</script>

<template>
    <OrganizationLayout
        title="New Journal Entry"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Journal Entries', href: index.url() },
            { label: 'New entry' },
        ]"
    >
        <Head title="New Journal Entry - Accounting" />

        <div class="mx-auto max-w-5xl space-y-6">
            <div class="flex items-center justify-between">
                <div><h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">New Journal Entry</h1><p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Every debit must have an equal credit before this entry can be saved.</p></div>
                <Link :href="index.url()"><Button variant="secondary" size="sm">Cancel</Button></Link>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Entry number <span class="text-rose-500">*</span></label>
                            <input v-model="form.entry_number" required class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 font-mono text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white" />
                            <p v-if="form.errors.entry_number" class="mt-1 text-xs text-rose-500">{{ form.errors.entry_number }}</p>
                        </div>
                        <DatePicker v-model="form.entry_date" label="Entry date" :required="true" :error="form.errors.entry_date" />
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Description <span class="text-rose-500">*</span></label>
                            <textarea v-model="form.description" required rows="2" placeholder="e.g. Monthly depreciation adjustment" class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white" />
                            <p v-if="form.errors.description" class="mt-1 text-xs text-rose-500">{{ form.errors.description }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex flex-col gap-3 border-b border-zinc-200 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
                        <div><h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Entry lines</h2><p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Only postable accounts can receive transactions.</p></div>
                        <div class="flex gap-2"><Button type="button" size="sm" variant="secondary" @click="addLine('debit')">Add debit</Button><Button type="button" size="sm" variant="secondary" @click="addLine('credit')">Add credit</Button></div>
                    </div>
                    <p v-if="form.errors.lines" class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">{{ form.errors.lines }}</p>
                    <div class="mt-4 space-y-3">
                        <div v-for="(line, lineIndex) in form.lines" :key="lineIndex" class="grid grid-cols-1 gap-3 rounded-lg border border-zinc-200 p-3 sm:grid-cols-[minmax(0,1fr)_8rem_8rem_auto] dark:border-zinc-800">
                            <Select v-model="line.account_id" :options="accountOptions" placeholder="Select account..." :searchable="true" :error="form.errors[`lines.${lineIndex}.account_id`]" />
                            <Select v-model="line.line_type" :options="[{ label: 'Debit', value: 'debit' }, { label: 'Credit', value: 'credit' }]" />
                            <div><input v-model="line.amount" min="0.01" step="0.01" type="number" placeholder="0.00" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 font-mono text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white" /><p v-if="form.errors[`lines.${lineIndex}.amount`]" class="mt-1 text-xs text-rose-500">{{ form.errors[`lines.${lineIndex}.amount`] }}</p></div>
                            <Button type="button" size="sm" variant="secondary" :disabled="form.lines.length === 2" @click="removeLine(lineIndex)">Remove</Button>
                            <input v-model="line.description" class="sm:col-span-3 rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white" placeholder="Line description (optional)" />
                        </div>
                    </div>
                    <div class="mt-5 flex flex-col gap-2 border-t border-zinc-200 pt-4 text-sm sm:flex-row sm:items-center sm:justify-end sm:gap-6 dark:border-zinc-800">
                        <span class="font-mono">Debit: <strong>{{ debitTotal.toFixed(2) }}</strong></span><span class="font-mono">Credit: <strong>{{ creditTotal.toFixed(2) }}</strong></span><span :class="isBalanced ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'" class="font-semibold">{{ isBalanced ? 'Balanced' : 'Not balanced' }}</span>
                    </div>
                </section>
                <div class="flex justify-end gap-3"><Link :href="index.url()"><Button type="button" variant="secondary">Cancel</Button></Link><Button type="submit" :disabled="form.processing || !isBalanced">{{ form.processing ? 'Saving...' : 'Save draft' }}</Button></div>
            </form>
        </div>
    </OrganizationLayout>
</template>
