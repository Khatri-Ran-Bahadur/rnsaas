<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';
import { usePermissions } from '@/composables/usePermissions';

interface AccountRef {
    id: number;
    code: string;
    name: string;
}

interface VendorRef {
    id: number;
    vendor_code: string;
    name: string;
    email: string | null;
    phone: string | null;
}

interface BillRef {
    id: number;
    bill_number: string;
    bill_date: string;
    due_date: string;
    total_amount: string;
    status: string;
    allocations?: Array<{ allocated_amount: string }>;
}

interface AllocationItem {
    id: number;
    purchase_bill_id: number;
    allocated_amount: string;
    allocated_at: string;
    purchase_bill?: BillRef | null;
}

interface PaymentData {
    id: number;
    payment_number: string;
    payment_date: string;
    amount: string;
    currency: string;
    payment_method: string;
    reference: string | null;
    notes: string | null;
    status: {
        value: string;
        label: string;
    } | string;
    vendor?: VendorRef | null;
    bank_account?: AccountRef | null;
    allocations?: AllocationItem[];
}

interface Props {
    payment: PaymentData;
    openBills: BillRef[];
}

const props = defineProps<Props>();

const { can, isAdmin } = usePermissions();
const canManage = ref(isAdmin.value || can('accounting.manage') || can('accounting.payments.manage'));

const isActionRunning = ref(false);
const isAllocateModalOpen = ref(false);

const getStatusValue = (status: PaymentData['status']) => {
    if (typeof status === 'object' && status !== null) {
        return status.value;
    }
    return status;
};

const formatCurrency = (val: string | number) => {
    const num = parseFloat(String(val) || '0');
    return new Intl.NumberFormat('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
};

// Calculate allocated and unallocated
const totalAllocated = computed(() => {
    if (!props.payment.allocations) return 0;
    return props.payment.allocations.reduce((sum, item) => sum + parseFloat(item.allocated_amount || '0'), 0);
});

const unallocatedBalance = computed(() => {
    const total = parseFloat(props.payment.amount || '0');
    return Math.max(0, total - totalAllocated.value);
});

// Bill remaining calculations
const getBillRemaining = (bill: BillRef) => {
    const total = parseFloat(bill.total_amount || '0');
    const allocated = (bill.allocations || []).reduce((sum, a) => sum + parseFloat(a.allocated_amount || '0'), 0);
    return Math.max(0, total - allocated);
};

// Allocation Form
const allocationInputs = ref<Record<number, number>>({});

const openAllocationDialog = () => {
    allocationInputs.value = {};
    isAllocateModalOpen.value = true;
};

const allocateForm = useForm({
    allocations: [] as Array<{ purchase_bill_id: number; amount: number }>,
});

const submitAllocations = () => {
    const allocationsPayload: Array<{ purchase_bill_id: number; amount: number }> = [];

    Object.entries(allocationInputs.value).forEach(([billId, amount]) => {
        const parsed = Number(amount);
        if (parsed > 0) {
            allocationsPayload.push({
                purchase_bill_id: Number(billId),
                amount: parsed,
            });
        }
    });

    if (allocationsPayload.length === 0) {
        alert('Please enter an amount to allocate to at least one open bill.');
        return;
    }

    allocateForm.allocations = allocationsPayload;
    allocateForm.post(`/admin/accounting/vendor-payments/${props.payment.id}/allocate`, {
        preserveScroll: true,
        onSuccess: () => {
            isAllocateModalOpen.value = false;
        },
    });
};

const postPayment = () => {
    if (isActionRunning.value) return;
    if (!confirm('Are you sure you want to post this payment to the General Ledger?')) {
        return;
    }
    isActionRunning.value = true;
    router.post(
        `/admin/accounting/vendor-payments/${props.payment.id}/post`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isActionRunning.value = false;
            },
        }
    );
};
</script>

<template>
    <OrganizationLayout
        :title="payment.payment_number"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Accounting', href: '/admin/accounting/vendors' },
            { label: 'Vendor Payments', href: '/admin/accounting/vendor-payments' },
            { label: payment.payment_number },
        ]"
    >
        <Head :title="`${payment.payment_number} - Vendor Payments`" />

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Action & Status Bar -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <span
                        :class="[
                            'inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider',
                            getStatusValue(payment.status) === 'posted' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' :
                            'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-700'
                        ]"
                    >
                        Status: {{ getStatusValue(payment.status) }}
                    </span>

                    <span v-if="payment.reference" class="text-xs text-zinc-500">
                        Ref: <strong class="font-mono text-zinc-700 dark:text-zinc-300">{{ payment.reference }}</strong>
                    </span>
                </div>

                <div v-if="canManage" class="flex flex-wrap items-center gap-2.5">
                    <!-- Allocate Button -->
                    <Button
                        v-if="unallocatedBalance > 0"
                        variant="secondary"
                        size="sm"
                        @click="openAllocationDialog"
                    >
                        Allocate to Bills
                    </Button>

                    <!-- Post Button -->
                    <Button
                        v-if="getStatusValue(payment.status) === 'draft'"
                        variant="primary"
                        size="sm"
                        :disabled="isActionRunning"
                        @click="postPayment"
                    >
                        {{ isActionRunning ? 'Posting...' : 'Post Payment to GL' }}
                    </Button>

                    <span v-if="getStatusValue(payment.status) === 'posted'" class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Posted to Ledger
                    </span>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Payment</p>
                    <p class="mt-2 text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                        {{ formatCurrency(payment.amount) }}
                    </p>
                    <p class="mt-1 text-[11px] text-zinc-400">Disbursed amount</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Allocated to Bills</p>
                    <p class="mt-2 text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                        {{ formatCurrency(totalAllocated) }}
                    </p>
                    <p class="mt-1 text-[11px] text-zinc-400">Settled against purchase bills</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Unallocated Balance</p>
                    <p class="mt-2 text-2xl font-bold font-mono text-indigo-600 dark:text-indigo-400">
                        {{ formatCurrency(unallocatedBalance) }}
                    </p>
                    <p class="mt-1 text-[11px] text-zinc-400">Available to allocate</p>
                </div>
            </div>

            <!-- Payment Details Card -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                    Payment Information
                </h2>

                <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <dl class="space-y-3 text-xs">
                        <div>
                            <dt class="font-medium text-zinc-400">Payment Number</dt>
                            <dd class="mt-0.5 font-mono font-semibold text-zinc-900 dark:text-zinc-100">{{ payment.payment_number }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-400">Vendor / Supplier</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100 font-medium">
                                <Link v-if="payment.vendor" :href="`/admin/accounting/vendors/${payment.vendor.id}`" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ payment.vendor.name }} ({{ payment.vendor.vendor_code }})
                                </Link>
                                <span v-else>Unknown</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-400">Payment Date</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ payment.payment_date }}</dd>
                        </div>
                    </dl>

                    <dl class="space-y-3 text-xs">
                        <div>
                            <dt class="font-medium text-zinc-400">Disbursed From (Bank / Cash Account)</dt>
                            <dd class="mt-0.5 font-mono font-medium text-zinc-900 dark:text-zinc-100">
                                {{ payment.bank_account ? `${payment.bank_account.code} - ${payment.bank_account.name}` : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-400">Payment Method</dt>
                            <dd class="mt-0.5 uppercase font-semibold text-zinc-900 dark:text-zinc-100 text-[11px]">{{ payment.payment_method }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-400">Reference #</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ payment.reference || 'None' }}</dd>
                        </div>
                    </dl>
                </div>

                <div v-if="payment.notes" class="mt-4 rounded-lg bg-zinc-50 p-3.5 dark:bg-zinc-800/60">
                    <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Notes / Memo</p>
                    <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">{{ payment.notes }}</p>
                </div>
            </div>

            <!-- Current Allocations Table -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-800">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        Allocated Purchase Bills
                    </h3>
                    <Button
                        v-if="canManage && unallocatedBalance > 0"
                        variant="secondary"
                        size="sm"
                        @click="openAllocationDialog"
                    >
                        + Add Allocation
                    </Button>
                </div>

                <div v-if="payment.allocations && payment.allocations.length > 0" class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-zinc-200 text-[11px] font-semibold uppercase text-zinc-400 dark:border-zinc-800">
                            <tr>
                                <th class="pb-3">Bill Number</th>
                                <th class="pb-3">Bill Date</th>
                                <th class="pb-3 text-right">Allocated Amount</th>
                                <th class="pb-3 text-right">Date Applied</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="alloc in payment.allocations" :key="alloc.id">
                                <td class="py-3 font-mono font-medium text-indigo-600 dark:text-indigo-400">
                                    <Link v-if="alloc.purchase_bill" :href="`/admin/accounting/purchase-bills/${alloc.purchase_bill.id}`" class="hover:underline">
                                        {{ alloc.purchase_bill.bill_number }}
                                    </Link>
                                    <span v-else>Bill #{{ alloc.purchase_bill_id }}</span>
                                </td>
                                <td class="py-3 text-zinc-600 dark:text-zinc-400">{{ alloc.purchase_bill?.bill_date || '-' }}</td>
                                <td class="py-3 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ formatCurrency(alloc.allocated_amount) }}
                                </td>
                                <td class="py-3 text-right text-zinc-500">{{ alloc.allocated_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="mt-4 text-center py-6 text-xs text-zinc-400 italic">
                    This payment has not yet been allocated to any purchase bills.
                    <div v-if="canManage && unallocatedBalance > 0" class="mt-2">
                        <Button variant="secondary" size="sm" @click="openAllocationDialog">
                            Allocate Now
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Allocation Modal Dialog -->
            <Modal :show="isAllocateModalOpen" max-width="2xl" @close="isAllocateModalOpen = false">
                <div class="p-6">
                    <div class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-800">
                        <div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                                Allocate Payment to Purchase Bills
                            </h3>
                            <p class="text-xs text-zinc-500 mt-0.5">
                                Available Unallocated Amount: <strong class="font-mono text-indigo-600 dark:text-indigo-400">{{ formatCurrency(unallocatedBalance) }}</strong>
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-md p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                            @click="isAllocateModalOpen = false"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div v-if="openBills.length > 0" class="mt-4 max-h-96 overflow-y-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="border-b border-zinc-200 text-[11px] font-semibold uppercase text-zinc-400 dark:border-zinc-800">
                                <tr>
                                    <th class="pb-2">Bill #</th>
                                    <th class="pb-2">Date</th>
                                    <th class="pb-2 text-right">Total</th>
                                    <th class="pb-2 text-right">Remaining Due</th>
                                    <th class="pb-2 w-32 text-right">Allocate ({{ payment.currency }})</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                <tr v-for="bill in openBills" :key="bill.id">
                                    <td class="py-2.5 font-mono font-medium text-indigo-600 dark:text-indigo-400">
                                        {{ bill.bill_number }}
                                    </td>
                                    <td class="py-2.5 text-zinc-600 dark:text-zinc-400">{{ bill.bill_date }}</td>
                                    <td class="py-2.5 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                        {{ formatCurrency(bill.total_amount) }}
                                    </td>
                                    <td class="py-2.5 text-right font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ formatCurrency(getBillRemaining(bill)) }}
                                    </td>
                                    <td class="py-2.5 text-right">
                                        <input
                                            v-model.number="allocationInputs[bill.id]"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            :max="getBillRemaining(bill)"
                                            placeholder="0.00"
                                            class="w-28 text-right font-mono rounded-md border border-zinc-300 bg-zinc-50 px-2 py-1 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="py-8 text-center text-xs text-zinc-400 italic">
                        No unpaid or issued purchase bills found for this vendor.
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                        <Button variant="secondary" size="sm" @click="isAllocateModalOpen = false">
                            Cancel
                        </Button>
                        <Button
                            v-if="openBills.length > 0"
                            variant="primary"
                            size="sm"
                            :disabled="allocateForm.processing"
                            @click="submitAllocations"
                        >
                            {{ allocateForm.processing ? 'Allocating...' : 'Apply Allocations' }}
                        </Button>
                    </div>
                </div>
            </Modal>
        </div>
    </OrganizationLayout>
</template>
