<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import StatsCard from "@/components/StatsCard.vue";
import Badge from "@/components/Badge.vue";
import Button from "@/components/Button.vue";
import Select from "@/components/Select.vue";
import Dropdown from "@/components/Dropdown.vue";
import Modal from "@/components/Modal.vue";
import Pagination from "@/components/Pagination.vue";
import EmptyState from "@/components/EmptyState.vue";
import ListGridToggle from "@/components/ListGridToggle.vue";
import PerPageSelector from "@/components/PerPageSelector.vue";
import SearchInput from "@/components/SearchInput.vue";
import FilterButton from "@/components/FilterButton.vue";

export interface TenantInfo {
	id: number;
	name: string;
}

export interface PlanInfo {
	id: number;
	name: string;
	price?: string;
	currency?: string;
	billing_cycle?: string;
}

export interface SubscriptionInfo {
	id: number;
	public_id: string;
	plan_id?: number;
	plan?: PlanInfo;
	status: string;
}

export interface PaymentTransaction {
	id: number;
	public_id: string;
	tenant_id: number;
	subscription_id: number | null;
	provider: string;
	provider_transaction_id: string | null;
	idempotency_key?: string;
	amount: string;
	currency: string;
	status: "pending" | "paid" | "failed" | "refunded";
	type: "subscription" | "renewal" | "addon" | "one_time";
	paid_at: string | null;
	created_at: string;
	metadata?: Record<string, any> | null;
	tenant?: TenantInfo;
	subscription?: SubscriptionInfo;
}

export interface PaginationLink {
	url: string | null;
	label: string;
	active: boolean;
}

export interface PaginatedPayments {
	data: PaymentTransaction[];
	current_page: number;
	last_page: number;
	per_page: number;
	total: number;
	from: number | null;
	to: number | null;
	links: PaginationLink[];
}

export interface Filters {
	search?: string;
	status?: string;
	provider?: string;
	per_page?: number | string;
}

const props = defineProps<{
	payments: PaginatedPayments;
	filters?: Filters;
	summary?: {
		total?: number;
		pending?: number;
		paid?: number;
		failed?: number;
		refunded?: number;
	};
}>();

// View and filter states
const viewMode = ref<"list" | "grid">("list");
const search = ref(props.filters?.search ?? "");
const status = ref(props.filters?.status ?? "");
const provider = ref(props.filters?.provider ?? "");
const perPage = ref(Number(props.filters?.per_page ?? 10));
const showFilters = ref(false);

const activeFiltersCount = computed(() => {
	return [status.value, provider.value].filter(Boolean).length;
});

const statusOptions = [
	{ label: "All Statuses", value: "" },
	{ label: "Pending", value: "pending" },
	{ label: "Paid", value: "paid" },
	{ label: "Failed", value: "failed" },
	{ label: "Refunded", value: "refunded" },
];

const providerOptions = [
	{ label: "All Providers", value: "" },
	{ label: "Bank Transfer", value: "bank_transfer" },
	{ label: "Stripe", value: "stripe" },
	{ label: "Razorpay", value: "razorpay" },
	{ label: "Khalti", value: "khalti" },
	{ label: "eSewa", value: "esewa" },
];

// Filter management
const applyFilters = () => {
	router.get(
		"/superadmin/payments",
		{
			search: search.value || undefined,
			status: status.value || undefined,
			provider: provider.value || undefined,
			per_page: perPage.value !== 10 ? perPage.value : undefined,
		},
		{
			preserveState: true,
			preserveScroll: true,
			replace: true,
		},
	);
};

// Reset filters
const resetFilters = () => {
	search.value = "";
	status.value = "";
	provider.value = "";
	applyFilters();
};

// Toggle filters
const toggleFilters = () => {
	showFilters.value = !showFilters.value;
};

// Handle per page change
const handlePerPageChange = (newPerPage: number) => {
	perPage.value = newPerPage;
	applyFilters();
};

const clearFilters = () => {
	search.value = "";
	status.value = "";
	provider.value = "";
	applyFilters();
};

// Summary metrics
const summaryStats = computed(() => {
	const totalCount = props.summary?.total ?? props.payments.total ?? 0;

	if (props.summary?.pending !== undefined) {
		return {
			total: totalCount,
			pending: props.summary.pending,
			paid: props.summary.paid ?? 0,
			failed: props.summary.failed ?? 0,
			refunded: props.summary.refunded ?? 0,
		};
	}

	return {
		total: totalCount,
		pending: "—",
		paid: "—",
		failed: "—",
		refunded: "—",
	};
});

// Helpers
const formatAmount = (amount: string | number, currency: string) => {
	const num = typeof amount === "string" ? parseFloat(amount) : amount;
	return `${currency?.toUpperCase() ?? "USD"} ${
		isNaN(num) ? "0.00" : num.toFixed(2)
	}`;
};

const formatProvider = (providerStr?: string | null) => {
	if (!providerStr) return "—";
	switch (providerStr.toLowerCase()) {
		case "bank_transfer":
			return "Bank Transfer";
		case "stripe":
			return "Stripe";
		case "razorpay":
			return "Razorpay";
		case "khalti":
			return "Khalti";
		case "esewa":
			return "eSewa";
		default:
			return providerStr
				.replace(/_/g, " ")
				.replace(/\b\w/g, (c) => c.toUpperCase());
	}
};

const formatPaymentType = (typeStr?: string | null) => {
	if (!typeStr) return "—";
	switch (typeStr.toLowerCase()) {
		case "subscription":
			return "Subscription";
		case "renewal":
			return "Renewal";
		case "addon":
			return "Addon";
		case "one_time":
			return "One-Time";
		default:
			return typeStr
				.replace(/_/g, " ")
				.replace(/\b\w/g, (c) => c.toUpperCase());
	}
};

const formatDate = (dateStr?: string | null) => {
	if (!dateStr) return "—";
	return new Date(dateStr).toLocaleDateString("en-US", {
		year: "numeric",
		month: "short",
		day: "numeric",
		hour: "2-digit",
		minute: "2-digit",
	});
};

const formatShortId = (publicId: string) => {
	if (!publicId) return "";
	return `#${publicId.substring(0, 10)}...`;
};

// Clipboard copy helper
const copiedId = ref<string | null>(null);
const copyToClipboard = async (text: string) => {
	try {
		await navigator.clipboard.writeText(text);
		copiedId.value = text;
		setTimeout(() => {
			copiedId.value = null;
		}, 2000);
	} catch {
		// Fallback
	}
};

// Detail Modal
const selectedPayment = ref<PaymentTransaction | null>(null);
const isDetailModalOpen = ref(false);

const openDetailModal = (payment: PaymentTransaction) => {
	selectedPayment.value = payment;
	isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
	isDetailModalOpen.value = false;
};

// Mark as Paid Confirmation Modal
const isConfirmModalOpen = ref(false);
const paymentToConfirm = ref<PaymentTransaction | null>(null);

const openConfirmModal = (payment: PaymentTransaction) => {
	paymentToConfirm.value = payment;
	isConfirmModalOpen.value = true;
};

const closeConfirmModal = () => {
	isConfirmModalOpen.value = false;
	paymentToConfirm.value = null;
};
</script>

<template>
	<SuperAdminLayout
		title="Payments"
		:breadcrumbs="[
			{ label: 'Dashboard', href: '/superadmin/dashboard' },
			{ label: 'Payments' },
		]"
	>
		<!-- Page Header -->
		<div
			class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-zinc-200 dark:border-zinc-800"
		>
			<div>
				<div class="flex items-center gap-2.5">
					<h1
						class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white"
					>
						Payments
					</h1>
					<span
						class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
					>
						{{ payments.total }} total
					</span>
				</div>
				<p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
					Manage subscription payments, bank transfers, and payment verification.
				</p>
			</div>
		</div>

		<!-- Summary Cards Grid -->
		<div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
			<!-- Total Payments -->
			<StatsCard
				title="Total Payments"
				:value="summaryStats.total"
				subtitle="All recorded transactions"
				badge-color="indigo"
			>
				<template #icon>
					<svg
						class="h-5 w-5"
						fill="none"
						viewBox="0 0 24 24"
						stroke="currentColor"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
						/>
					</svg>
				</template>
			</StatsCard>

			<!-- Pending -->
			<StatsCard
				title="Pending"
				:value="summaryStats.pending"
				:subtitle="
					summaryStats.pending === '—'
						? 'Awaiting server aggregate'
						: 'Awaiting verification'
				"
				badge-color="amber"
			>
				<template #icon>
					<svg
						class="h-5 w-5"
						fill="none"
						viewBox="0 0 24 24"
						stroke="currentColor"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
						/>
					</svg>
				</template>
			</StatsCard>

			<!-- Paid -->
			<StatsCard
				title="Paid"
				:value="summaryStats.paid"
				subtitle="Successfully completed"
				badge-color="emerald"
			>
				<template #icon>
					<svg
						class="h-5 w-5"
						fill="none"
						viewBox="0 0 24 24"
						stroke="currentColor"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
						/>
					</svg>
				</template>
			</StatsCard>

			<!-- Failed -->
			<StatsCard
				title="Failed"
				:value="summaryStats.failed"
				subtitle="Unsuccessful attempts"
				badge-color="rose"
			>
				<template #icon>
					<svg
						class="h-5 w-5"
						fill="none"
						viewBox="0 0 24 24"
						stroke="currentColor"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
						/>
					</svg>
				</template>
			</StatsCard>

			<!-- Refunded -->
			<StatsCard
				title="Refunded"
				:value="summaryStats.refunded"
				subtitle="Reversed transactions"
				badge-color="zinc"
			>
				<template #icon>
					<svg
						class="h-5 w-5"
						fill="none"
						viewBox="0 0 24 24"
						stroke="currentColor"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M16 15v-1a4 4 0 00-4-4H4m0 0l3-3m-3 3l3 3m5 4v1a4 4 0 004 4h8m0 0l-3-3m3 3l-3 3"
						/>
					</svg>
				</template>
			</StatsCard>
		</div>

		<!-- Main Content Card (Accounting style: search + controls header, table/grid, and inside pagination) -->
		<div
			class="mt-6 overflow-hidden rounded-xl border border-zinc-200/90 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
		>
			<!-- Search & Controls Header -->
			<div
				class="p-4 sm:p-5 border-b border-zinc-200/90 bg-zinc-50/50 dark:border-zinc-800 dark:bg-zinc-900/60"
			>
				<div
					class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4"
				>
					<!-- Search Input -->
					<div class="flex-1 max-w-md">
						<SearchInput
							v-model="search"
							placeholder="Search by ID, tenant, provider ref..."
							@search="applyFilters"
							@clear="clearFilters"
						/>
					</div>

					<!-- Right Controls -->
					<div class="flex items-center gap-2.5 self-end sm:self-auto">
						<!-- List / Grid Toggle -->
						<ListGridToggle v-model="viewMode" />

						<!-- Per Page Selector -->
						<PerPageSelector
							v-model="perPage"
							@change="handlePerPageChange"
						/>

						<!-- Filter Button -->
						<FilterButton
							:show-filters="showFilters"
							:count="activeFiltersCount"
							@toggle="showFilters = !showFilters"
						/>
					</div>
				</div>
			</div>

			<!-- Advanced Filters Row (Expandable) -->
			<div
				v-if="showFilters"
				class="p-4 sm:p-5 bg-zinc-50/90 border-b border-zinc-200/90 dark:bg-zinc-900/90 dark:border-zinc-800"
			>
				<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 items-end">
					<!-- Status Filter -->
					<div>
						<Select
							v-model="status"
							label="Status"
							:options="statusOptions"
							placeholder="All Statuses"
						/>
					</div>

					<!-- Provider Filter -->
					<div>
						<Select
							v-model="provider"
							label="Provider"
							:options="providerOptions"
							placeholder="All Providers"
						/>
					</div>

					<!-- Actions -->
					<div class="flex items-center gap-2">
						<Button
							type="button"
							variant="primary"
							size="sm"
							@click="applyFilters"
						>
							Apply Filters
						</Button>
						<Button
							v-if="search || status || provider"
							type="button"
							variant="outline"
							size="sm"
							@click="clearFilters"
						>
							Clear
						</Button>
					</div>
				</div>
			</div>

			<!-- Content Area: Empty State -->
			<div v-if="payments.data.length === 0" class="p-12 text-center">
				<EmptyState
					title="No payments found"
					:description="
						search || status || provider
							? 'No payments matched the selected search criteria.'
							: 'No payment transactions have been recorded yet.'
					"
				>
					<template #actions>
						<Button
							v-if="search || status || provider"
							variant="outline"
							size="sm"
							@click="clearFilters"
						>
							Reset Filters
						</Button>
					</template>
				</EmptyState>
			</div>

			<!-- Content Area: List View (Table) -->
			<div v-else-if="viewMode === 'list'" class="overflow-x-auto">
				<table
					class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300"
				>
					<thead
						class="border-b border-zinc-200 bg-zinc-100/75 text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-300"
					>
						<tr>
							<th scope="col" class="h-11 px-4 text-left align-middle">Payment</th>
							<th scope="col" class="h-11 px-4 text-left align-middle">Tenant</th>
							<th scope="col" class="h-11 px-4 text-left align-middle">Subscription / Plan</th>
							<th scope="col" class="h-11 px-4 text-left align-middle">Provider</th>
							<th scope="col" class="h-11 px-4 text-left align-middle">Amount</th>
							<th scope="col" class="h-11 px-4 text-left align-middle">Status</th>
							<th scope="col" class="h-11 px-4 text-left align-middle">Paid At</th>
							<th scope="col" class="h-11 px-4 text-left align-middle">Created</th>
							<th scope="col" class="h-11 px-4 text-right align-middle">
								Actions
							</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-zinc-200/80 dark:divide-zinc-800/80">
						<tr
							v-for="item in payments.data"
							:key="item.id"
							class="transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40"
						>
							<!-- 1. Payment ID -->
							<td class="p-4 align-middle font-medium text-zinc-900 dark:text-zinc-100">
								<div class="flex items-center gap-1.5">
									<span
										class="font-mono text-xs font-medium text-zinc-900 dark:text-zinc-100"
										:title="item.public_id"
									>
										{{ formatShortId(item.public_id) }}
									</span>
									<button
										type="button"
										class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-pointer"
										:title="
											copiedId === item.public_id ? 'Copied!' : 'Copy full ID'
										"
										@click="copyToClipboard(item.public_id)"
									>
										<svg
											v-if="copiedId === item.public_id"
											class="h-3.5 w-3.5 text-emerald-600"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2.5"
												d="M5 13l4 4L19 7"
											/>
										</svg>
										<svg
											v-else
											class="h-3.5 w-3.5"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="2"
												d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
											/>
										</svg>
									</button>
								</div>
							</td>

							<!-- 2. Tenant -->
							<td
								class="p-4 align-middle font-medium text-zinc-900 dark:text-zinc-100"
							>
								<div class="flex items-center gap-2.5">
									<div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 font-bold text-xs text-zinc-700 uppercase dark:bg-zinc-800 dark:text-zinc-300 border border-zinc-200/60 dark:border-zinc-700">
										{{ (item.tenant?.name ?? 'T').substring(0, 2) }}
									</div>
									<div class="truncate max-w-[160px]" :title="item.tenant?.name">
										{{ item.tenant?.name ?? `Tenant #${item.tenant_id}` }}
									</div>
								</div>
							</td>

							<!-- 3. Subscription / Plan -->
							<td class="px-4 py-4 text-xs font-medium text-zinc-700 dark:text-zinc-300">
								<div
									v-if="item.subscription?.plan?.name"
									class="font-semibold text-zinc-900 dark:text-zinc-100"
								>
									{{ item.subscription.plan.name }}
								</div>
								<div
									v-else-if="item.subscription_id"
									class="text-xs text-zinc-500 dark:text-zinc-400 font-mono"
								>
									Sub #{{ item.subscription_id }}
								</div>
								<div v-else class="text-xs text-zinc-400">—</div>
							</td>

							<!-- 4. Provider -->
							<td class="px-4 py-4">
								<div
									class="inline-flex items-center gap-1.5 text-xs text-zinc-700 dark:text-zinc-300"
								>
									<span class="h-1.5 w-1.5 rounded-full bg-zinc-400 shrink-0" />
									<span>{{ formatProvider(item.provider) }}</span>
								</div>
							</td>

							<!-- 5. Amount -->
							<td
								class="px-4 py-4 font-semibold text-zinc-900 dark:text-zinc-100 whitespace-nowrap"
							>
								{{ formatAmount(item.amount, item.currency) }}
							</td>

							<!-- 6. Status -->
							<td class="px-4 py-4 whitespace-nowrap">
								<Badge
									v-if="item.status === 'paid'"
									variant="success"
									label="Paid"
								/>
								<Badge
									v-else-if="item.status === 'pending'"
									variant="warning"
									label="Pending"
								/>
								<Badge
									v-else-if="item.status === 'failed'"
									variant="danger"
									label="Failed"
								/>
								<Badge
									v-else-if="item.status === 'refunded'"
									variant="default"
									label="Refunded"
								/>
								<Badge v-else variant="default" :label="item.status" />
							</td>

							<!-- 7. Paid At -->
							<td
								class="px-4 py-4 font-mono text-xs whitespace-nowrap text-zinc-500 dark:text-zinc-400"
							>
								{{ formatDate(item.paid_at) }}
							</td>

							<!-- 8. Created At -->
							<td
								class="px-4 py-4 font-mono text-xs whitespace-nowrap text-zinc-500 dark:text-zinc-400"
							>
								{{ formatDate(item.created_at) }}
							</td>

							<!-- 9. Actions (Teleported 3-dots Dropdown) -->
							<td class="p-4 align-middle text-right whitespace-nowrap">
								<Dropdown align="right" width="w-48">
									<template #trigger>
										<button
											type="button"
											class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 transition-colors cursor-pointer"
											title="Actions"
										>
											<span class="sr-only">Open menu</span>
											<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
											</svg>
										</button>
									</template>

									<template #default="{ close }">
										<div class="py-1 text-xs">
											<!-- View Details -->
											<button
												type="button"
												class="w-full flex items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md text-left cursor-pointer"
												@click="openDetailModal(item); close()"
											>
												<svg class="h-4 w-4 text-zinc-500 dark:text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
												</svg>
												<span>View Details</span>
											</button>

											<!-- Mark as Paid (Only for pending) -->
											<button
												v-if="item.status === 'pending'"
												type="button"
												class="w-full flex items-center gap-2.5 px-3 py-2 text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40 transition-colors rounded-md text-left cursor-pointer"
												@click="openConfirmModal(item); close()"
											>
												<svg class="h-4 w-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
												</svg>
												<span>Mark as Paid</span>
											</button>
										</div>
									</template>
								</Dropdown>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Content Area: Grid View (Accounting-style Cards) -->
			<div
				v-else-if="viewMode === 'grid'"
				class="p-5 bg-zinc-50/30 dark:bg-zinc-950/30"
			>
				<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
					<div
						v-for="item in payments.data"
						:key="item.id"
						class="flex flex-col justify-between rounded-xl border border-zinc-200 bg-white p-4 shadow-2xs hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 transition-all"
					>
						<!-- Top Info -->
						<div>
							<div class="flex items-center gap-3 mb-3">
								<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 font-bold text-sm text-zinc-800 uppercase dark:bg-zinc-800 dark:text-zinc-200 border border-zinc-200/80 dark:border-zinc-700">
									{{ (item.tenant?.name ?? 'T').substring(0, 2) }}
								</div>
								<div class="flex-1 min-w-0">
									<h3 class="font-semibold text-sm text-zinc-900 dark:text-white truncate" :title="item.tenant?.name">
										{{ item.tenant?.name ?? `Tenant #${item.tenant_id}` }}
									</h3>
									<span class="font-mono text-[11px] text-zinc-400 dark:text-zinc-500 truncate block">
										{{ formatShortId(item.public_id) }}
									</span>
								</div>
							</div>

							<!-- Details -->
							<div class="space-y-2 text-xs">
								<div class="flex items-center justify-between">
									<span class="text-zinc-500 dark:text-zinc-400 text-[11px] font-medium">Amount</span>
									<span class="text-sm font-bold text-zinc-900 dark:text-white">
										{{ formatAmount(item.amount, item.currency) }}
									</span>
								</div>

								<div class="flex items-center justify-between pt-1">
									<span class="text-zinc-500 dark:text-zinc-400 text-[11px] font-medium">Provider</span>
									<span class="font-medium text-zinc-800 dark:text-zinc-200">
										{{ formatProvider(item.provider) }}
									</span>
								</div>

								<div v-if="item.subscription?.plan?.name" class="flex items-center justify-between pt-1">
									<span class="text-zinc-500 dark:text-zinc-400 text-[11px] font-medium">Plan</span>
									<span class="font-medium text-zinc-800 dark:text-zinc-200 truncate max-w-[120px]">
										{{ item.subscription.plan.name }}
									</span>
								</div>

								<div class="pt-1">
									<span class="text-zinc-500 dark:text-zinc-400 block text-[11px] font-medium">Date</span>
									<span class="font-mono text-zinc-600 dark:text-zinc-400 text-[11px]">
										{{ formatDate(item.paid_at || item.created_at) }}
									</span>
								</div>
							</div>
						</div>

						<!-- Card Footer -->
						<div class="mt-4 pt-3 border-t border-dashed border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
							<!-- Status Badge -->
							<Badge
								v-if="item.status === 'paid'"
								variant="success"
								label="Paid"
							/>
							<Badge
								v-else-if="item.status === 'pending'"
								variant="warning"
								label="Pending"
							/>
							<Badge
								v-else-if="item.status === 'failed'"
								variant="danger"
								label="Failed"
							/>
							<Badge
								v-else-if="item.status === 'refunded'"
								variant="default"
								label="Refunded"
							/>
							<Badge v-else variant="default" :label="item.status" />

							<!-- Actions Dropdown -->
							<Dropdown align="right" width="w-48">
								<template #trigger>
									<button
										type="button"
										class="inline-flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-white transition-colors cursor-pointer"
										title="Actions"
									>
										<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
										</svg>
									</button>
								</template>

								<template #default="{ close }">
									<div class="py-1 text-xs">
										<button
											type="button"
											class="w-full flex items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md text-left cursor-pointer"
											@click="openDetailModal(item); close()"
										>
											<svg class="h-4 w-4 text-zinc-500 dark:text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
											</svg>
											<span>View Details</span>
										</button>

										<button
											v-if="item.status === 'pending'"
											type="button"
											class="w-full flex items-center gap-2.5 px-3 py-2 text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40 transition-colors rounded-md text-left cursor-pointer"
											@click="openConfirmModal(item); close()"
										>
											<svg class="h-4 w-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
											</svg>
											<span>Mark as Paid</span>
										</button>
									</div>
								</template>
							</Dropdown>
						</div>
					</div>
				</div>
			</div>

			<!-- Card Footer Pagination (Inside the card container) -->
			<div
				v-if="payments.total > 0"
				class="border-t border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900"
			>
				<Pagination :data="payments" />
			</div>
		</div>

		<!-- Detail Modal -->
		<Modal
			:show="isDetailModalOpen"
			title="Payment Transaction Details"
			description="Comprehensive financial record and verification details."
			max-width="lg"
			@close="closeDetailModal"
		>
			<div v-if="selectedPayment" class="space-y-6">
				<!-- Top Summary Card -->
				<div
					class="flex items-center justify-between rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/60 border border-zinc-200/70 dark:border-zinc-700/60"
				>
					<div>
						<p
							class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
						>
							Transaction Amount
						</p>
						<h4
							class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white"
						>
							{{
								formatAmount(selectedPayment.amount, selectedPayment.currency)
							}}
						</h4>
					</div>
					<div class="text-right">
						<Badge
							v-if="selectedPayment.status === 'paid'"
							variant="success"
							size="md"
							label="Paid"
						/>
						<Badge
							v-else-if="selectedPayment.status === 'pending'"
							variant="warning"
							size="md"
							label="Pending"
						/>
						<Badge
							v-else-if="selectedPayment.status === 'failed'"
							variant="danger"
							size="md"
							label="Failed"
						/>
						<Badge
							v-else-if="selectedPayment.status === 'refunded'"
							variant="default"
							size="md"
							label="Refunded"
						/>
						<Badge
							v-else
							variant="default"
							size="md"
							:label="selectedPayment.status"
						/>
						<p class="mt-1 font-mono text-[11px] text-zinc-400">
							{{ formatPaymentType(selectedPayment.type) }}
						</p>
					</div>
				</div>

				<!-- Two-Column Details Grid -->
				<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-xs">
					<!-- Payment ID -->
					<div
						class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/30"
					>
						<span class="font-medium text-zinc-500 dark:text-zinc-400"
							>Public ID</span
						>
						<div
							class="mt-1 flex items-center justify-between font-mono font-semibold text-zinc-900 dark:text-zinc-100"
						>
							<span class="truncate" :title="selectedPayment.public_id">{{
								selectedPayment.public_id
							}}</span>
							<button
								type="button"
								class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 shrink-0 ml-2 cursor-pointer"
								title="Copy"
								@click="copyToClipboard(selectedPayment.public_id)"
							>
								<svg
									v-if="copiedId === selectedPayment.public_id"
									class="h-3.5 w-3.5 text-emerald-600"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2.5"
										d="M5 13l4 4L19 7"
									/>
								</svg>
								<svg
									v-else
									class="h-3.5 w-3.5"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
									/>
								</svg>
							</button>
						</div>
					</div>

					<!-- Tenant -->
					<div
						class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/30"
					>
						<span class="font-medium text-zinc-500 dark:text-zinc-400"
							>Tenant / Organization</span
						>
						<p
							class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100 truncate"
						>
							{{
								selectedPayment.tenant?.name ??
								`Tenant #${selectedPayment.tenant_id}`
							}}
						</p>
					</div>

					<!-- Plan -->
					<div
						class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/30"
					>
						<span class="font-medium text-zinc-500 dark:text-zinc-400"
							>Subscription Plan</span
						>
						<p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">
							{{ selectedPayment.subscription?.plan?.name ?? "—" }}
						</p>
					</div>

					<!-- Subscription Ref -->
					<div
						class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/30"
					>
						<span class="font-medium text-zinc-500 dark:text-zinc-400"
							>Subscription Reference</span
						>
						<p
							class="mt-1 font-mono font-semibold text-zinc-900 dark:text-zinc-100"
						>
							{{
								selectedPayment.subscription?.public_id ??
								(selectedPayment.subscription_id
									? `#${selectedPayment.subscription_id}`
									: "—")
							}}
						</p>
					</div>

					<!-- Provider -->
					<div
						class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/30"
					>
						<span class="font-medium text-zinc-500 dark:text-zinc-400"
							>Payment Provider</span
						>
						<p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">
							{{ formatProvider(selectedPayment.provider) }}
						</p>
					</div>

					<!-- Provider Transaction ID -->
					<div
						class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/30"
					>
						<span class="font-medium text-zinc-500 dark:text-zinc-400"
							>Provider Transaction Ref</span
						>
						<p class="mt-1 font-mono text-zinc-900 dark:text-zinc-100">
							{{ selectedPayment.provider_transaction_id ?? "—" }}
						</p>
					</div>

					<!-- Created At -->
					<div
						class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/30"
					>
						<span class="font-medium text-zinc-500 dark:text-zinc-400"
							>Created At</span
						>
						<p class="mt-1 font-mono text-zinc-900 dark:text-zinc-100">
							{{ formatDate(selectedPayment.created_at) }}
						</p>
					</div>

					<!-- Paid At -->
					<div
						class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3 dark:border-zinc-800 dark:bg-zinc-800/30"
					>
						<span class="font-medium text-zinc-500 dark:text-zinc-400"
							>Paid At</span
						>
						<p class="mt-1 font-mono text-zinc-900 dark:text-zinc-100">
							{{
								selectedPayment.paid_at
									? formatDate(selectedPayment.paid_at)
									: "Not paid yet"
							}}
						</p>
					</div>
				</div>

				<!-- Metadata Section -->
				<div
					v-if="
						selectedPayment.metadata &&
						Object.keys(selectedPayment.metadata).length > 0
					"
					class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-900/60"
				>
					<p
						class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2"
					>
						Transaction Metadata
					</p>
					<pre
						class="overflow-x-auto text-[11px] font-mono text-zinc-700 dark:text-zinc-300 p-2 rounded-md bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800"
						>{{ JSON.stringify(selectedPayment.metadata, null, 2) }}</pre
					>
				</div>
			</div>

			<!-- Modal Footer -->
			<template #footer>
				<Button variant="outline" size="sm" @click="closeDetailModal">
					Close
				</Button>

				<Button
					v-if="selectedPayment?.status === 'pending'"
					variant="success"
					size="sm"
					@click="closeDetailModal(); openConfirmModal(selectedPayment!)"
				>
					<template #prefix>
						<svg
							class="h-3.5 w-3.5"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M5 13l4 4L19 7"
							/>
						</svg>
					</template>
					<span>Mark as Paid</span>
				</Button>
			</template>
		</Modal>

		<!-- Mark as Paid Confirmation Modal -->
		<Modal
			:show="isConfirmModalOpen"
			title="Confirm Payment"
			description="Are you sure this bank transfer has been verified and should be marked as paid?"
			max-width="md"
			@close="closeConfirmModal"
		>
			<div v-if="paymentToConfirm" class="space-y-4">
				<!-- Transaction Summary Box -->
				<div
					class="rounded-xl border border-amber-200/80 bg-amber-50/60 p-4 dark:border-amber-900/60 dark:bg-amber-950/40 text-xs text-amber-900 dark:text-amber-200"
				>
					<div
						class="flex items-center gap-2 mb-2 font-semibold text-amber-800 dark:text-amber-300"
					>
						<svg
							class="h-4 w-4 shrink-0"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
							/>
						</svg>
						<span>Manual Financial Verification</span>
					</div>
					<p class="leading-relaxed">
						Marking this bank transfer as paid will transition the payment to
						<strong>Paid</strong>, trigger the
						<strong>PaymentPaid</strong> event, activate the associated tenant
						subscription, and record an audit log.
					</p>
				</div>

				<div
					class="rounded-xl border border-zinc-200 bg-white p-3.5 dark:border-zinc-800 dark:bg-zinc-900 text-xs space-y-2"
				>
					<div class="flex items-center justify-between">
						<span class="text-zinc-500 dark:text-zinc-400">Payment ID:</span>
						<span
							class="font-mono font-medium text-zinc-900 dark:text-zinc-100"
							>{{ formatShortId(paymentToConfirm.public_id) }}</span
						>
					</div>
					<div class="flex items-center justify-between">
						<span class="text-zinc-500 dark:text-zinc-400">Tenant:</span>
						<span class="font-medium text-zinc-900 dark:text-zinc-100">{{
							paymentToConfirm.tenant?.name ??
							`Tenant #${paymentToConfirm.tenant_id}`
						}}</span>
					</div>
					<div class="flex items-center justify-between">
						<span class="text-zinc-500 dark:text-zinc-400">Amount:</span>
						<span class="font-bold text-zinc-900 dark:text-zinc-100">{{
							formatAmount(paymentToConfirm.amount, paymentToConfirm.currency)
						}}</span>
					</div>
					<div class="flex items-center justify-between">
						<span class="text-zinc-500 dark:text-zinc-400">Provider:</span>
						<span class="font-medium text-zinc-900 dark:text-zinc-100">{{
							formatProvider(paymentToConfirm.provider)
						}}</span>
					</div>
				</div>

				<!-- Backend Route Notice -->
				<div
					class="rounded-lg bg-zinc-100 dark:bg-zinc-800/80 p-3 text-xs text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700"
				>
					<div
						class="flex items-center gap-1.5 font-medium text-zinc-800 dark:text-zinc-200 mb-1"
					>
						<span class="h-1.5 w-1.5 rounded-full bg-amber-500" />
						<span>Awaiting Backend Route</span>
					</div>
					<p class="text-[11px] leading-relaxed">
						The backend action
						<code
							class="px-1 py-0.5 rounded bg-zinc-200 dark:bg-zinc-900 font-mono text-[10px]"
							>MarkPaymentAsPaidAction</code
						>
						exists, but its HTTP controller route (e.g.,
						<code
							class="px-1 py-0.5 rounded bg-zinc-200 dark:bg-zinc-900 font-mono text-[10px]"
							>POST /admin/payments/{payment}/mark-as-paid</code
						>) is not yet registered. Action is paused awaiting route hookup.
					</p>
				</div>
			</div>

			<template #footer>
				<Button variant="outline" size="sm" @click="closeConfirmModal">
					Cancel
				</Button>

				<Button
					variant="success"
					size="sm"
					:disabled="true"
					title="Awaiting backend HTTP route integration"
				>
					<template #prefix>
						<svg
							class="h-3.5 w-3.5"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M5 13l4 4L19 7"
							/>
						</svg>
					</template>
					<span>Confirm Payment</span>
				</Button>
			</template>
		</Modal>
	</SuperAdminLayout>
</template>
