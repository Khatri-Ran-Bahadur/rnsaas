<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import AdminLayout from "@/layouts/AdminLayout.vue";
import StatsCard from "@/components/StatsCard.vue";
import Badge from "@/components/Badge.vue";
import Button from "@/components/Button.vue";
import Modal from "@/components/Modal.vue";
import Pagination from "@/components/Pagination.vue";
import EmptyState from "@/components/EmptyState.vue";

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

// Filter states
const search = ref(props.filters?.search ?? "");
const status = ref(props.filters?.status ?? "");
const provider = ref(props.filters?.provider ?? "");

// Filter management
const applyFilters = () => {
	router.get(
		"/admin/payments",
		{
			search: search.value || undefined,
			status: status.value || undefined,
			provider: provider.value || undefined,
		},
		{
			preserveState: true,
			preserveScroll: true,
			replace: true,
		}
	);
};

const clearFilters = () => {
	search.value = "";
	status.value = "";
	provider.value = "";
	applyFilters();
};

// Summary metrics (utilizing server aggregates if available, otherwise total + page counts)
const summaryStats = computed(() => {
	const totalCount = props.summary?.total ?? props.payments.total ?? 0;

	// Check if server passed breakdown counts
	if (props.summary?.pending !== undefined) {
		return {
			total: totalCount,
			pending: props.summary.pending,
			paid: props.summary.paid ?? 0,
			failed: props.summary.failed ?? 0,
			refunded: props.summary.refunded ?? 0,
		};
	}

	// Otherwise show exact total, and indicate server aggregate is pending for specific breakdowns
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
		// Fallback or ignore
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
	<AdminLayout
		title="Payments"
		:breadcrumbs="[
			{ label: 'Dashboard', href: '/admin/dashboard' },
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
					Manage subscription payments, bank transfers, and payment
					verification.
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
				:subtitle="
					summaryStats.paid === '—'
						? 'Awaiting server aggregate'
						: 'Successfully completed'
				"
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
				:subtitle="
					summaryStats.failed === '—'
						? 'Awaiting server aggregate'
						: 'Declined / Failed'
				"
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
				:subtitle="
					summaryStats.refunded === '—'
						? 'Awaiting server aggregate'
						: 'Reversed payments'
				"
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
							d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"
						/>
					</svg>
				</template>
			</StatsCard>
		</div>

		<!-- Filter & Search Bar -->
		<div
			class="mt-6 rounded-xl border border-zinc-200 bg-white p-4 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900"
		>
			<form
				@submit.prevent="applyFilters"
				class="flex flex-col lg:flex-row lg:items-center gap-3"
			>
				<!-- Search Input -->
				<div class="relative flex-1">
					<div
						class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400"
					>
						<svg
							class="h-4 w-4"
							fill="none"
							viewBox="0 0 24 24"
							stroke="currentColor"
						>
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
							/>
						</svg>
					</div>
					<input
						v-model="search"
						type="text"
						placeholder="Search payments..."
						class="w-full rounded-lg border border-zinc-200 bg-white py-2 pl-9 pr-3 text-sm text-zinc-900 placeholder-zinc-400 focus:border-primary-500 focus:outline-hidden focus:ring-2 focus:ring-primary-500/20 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white dark:placeholder-zinc-500 dark:focus:border-primary-500"
					/>
				</div>

				<!-- Status Filter Dropdown -->
				<div class="w-full sm:w-44">
					<select
						v-model="status"
						class="w-full rounded-lg border border-zinc-200 bg-white py-2 px-3 text-sm text-zinc-900 focus:border-primary-500 focus:outline-hidden focus:ring-2 focus:ring-primary-500/20 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white dark:focus:border-primary-500"
					>
						<option value="">All Statuses</option>
						<option value="pending">Pending</option>
						<option value="paid">Paid</option>
						<option value="failed">Failed</option>
						<option value="refunded">Refunded</option>
					</select>
				</div>

				<!-- Provider Filter Dropdown -->
				<div class="w-full sm:w-44">
					<select
						v-model="provider"
						class="w-full rounded-lg border border-zinc-200 bg-white py-2 px-3 text-sm text-zinc-900 focus:border-primary-500 focus:outline-hidden focus:ring-2 focus:ring-primary-500/20 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white dark:focus:border-primary-500"
					>
						<option value="">All Providers</option>
						<option value="bank_transfer">Bank Transfer</option>
					</select>
				</div>

				<!-- Action Buttons -->
				<div class="flex items-center gap-2 shrink-0">
					<Button type="submit" variant="primary" size="sm">
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
									d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
								/>
							</svg>
						</template>
						<span>Search</span>
					</Button>

					<Button
						v-if="search || status || provider"
						type="button"
						variant="outline"
						size="sm"
						@click="clearFilters"
					>
						<span>Clear</span>
					</Button>
				</div>
			</form>
		</div>

		<!-- Payments Table Section -->
		<div
			class="mt-6 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xs dark:border-zinc-800 dark:bg-zinc-900"
		>
			<div class="overflow-x-auto">
				<table
					class="w-full text-left text-sm text-zinc-600 dark:text-zinc-400"
				>
					<thead
						class="border-b border-zinc-200 bg-zinc-50/75 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/75 dark:text-zinc-400"
					>
						<tr>
							<th scope="col" class="py-3.5 pl-4 pr-3 sm:pl-6">Payment</th>
							<th scope="col" class="px-3 py-3.5">Tenant</th>
							<th scope="col" class="px-3 py-3.5">Subscription / Plan</th>
							<th scope="col" class="px-3 py-3.5">Provider</th>
							<th scope="col" class="px-3 py-3.5">Amount</th>
							<th scope="col" class="px-3 py-3.5">Status</th>
							<th scope="col" class="px-3 py-3.5">Paid At</th>
							<th scope="col" class="px-3 py-3.5">Created</th>
							<th scope="col" class="py-3.5 pl-3 pr-4 sm:pr-6 text-right">
								Actions
							</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
						<tr
							v-for="item in payments.data"
							:key="item.id"
							class="transition-colors hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40"
						>
							<!-- 1. Payment ID -->
							<td class="py-4 pl-4 pr-3 sm:pl-6">
								<div class="flex items-center gap-1.5">
									<span
										class="font-mono text-xs font-medium text-zinc-900 dark:text-zinc-100"
										:title="item.public_id"
									>
										{{ formatShortId(item.public_id) }}
									</span>
									<button
										type="button"
										class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
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
								class="px-3 py-4 font-medium text-zinc-900 dark:text-zinc-100"
							>
								<div class="truncate max-w-[160px]" :title="item.tenant?.name">
									{{ item.tenant?.name ?? `Tenant #${item.tenant_id}` }}
								</div>
							</td>

							<!-- 3. Subscription / Plan -->
							<td class="px-3 py-4">
								<div
									v-if="item.subscription?.plan?.name"
									class="font-medium text-zinc-900 dark:text-zinc-100"
								>
									{{ item.subscription.plan.name }}
								</div>
								<div
									v-else-if="item.subscription_id"
									class="text-xs text-zinc-500 dark:text-zinc-400"
								>
									Sub #{{ item.subscription_id }}
								</div>
								<div v-else class="text-xs text-zinc-400">—</div>
							</td>

							<!-- 4. Provider -->
							<td class="px-3 py-4">
								<div
									class="inline-flex items-center gap-1.5 text-xs text-zinc-700 dark:text-zinc-300"
								>
									<span class="h-1.5 w-1.5 rounded-full bg-zinc-400 shrink-0" />
									<span>{{ formatProvider(item.provider) }}</span>
								</div>
							</td>

							<!-- 5. Amount -->
							<td
								class="px-3 py-4 font-semibold text-zinc-900 dark:text-zinc-100 whitespace-nowrap"
							>
								{{ formatAmount(item.amount, item.currency) }}
							</td>

							<!-- 6. Status -->
							<td class="px-3 py-4 whitespace-nowrap">
								<Badge
									v-if="item.status === 'paid'"
									variant="active"
									label="Paid"
								/>
								<Badge
									v-else-if="item.status === 'pending'"
									variant="pending"
									label="Pending"
								/>
								<Badge
									v-else-if="item.status === 'failed'"
									variant="cancelled"
									label="Failed"
								/>
								<Badge
									v-else-if="item.status === 'refunded'"
									variant="neutral"
									label="Refunded"
								/>
								<Badge v-else variant="default" :label="item.status" />
							</td>

							<!-- 7. Paid At -->
							<td
								class="px-3 py-4 font-mono text-xs whitespace-nowrap text-zinc-500 dark:text-zinc-400"
							>
								{{ formatDate(item.paid_at) }}
							</td>

							<!-- 8. Created At -->
							<td
								class="px-3 py-4 font-mono text-xs whitespace-nowrap text-zinc-500 dark:text-zinc-400"
							>
								{{ formatDate(item.created_at) }}
							</td>

							<!-- 9. Actions -->
							<td class="py-4 pl-3 pr-4 sm:pr-6 text-right whitespace-nowrap">
								<div class="flex items-center justify-end gap-2">
									<!-- View Details Button -->
									<Button
										variant="outline"
										size="xs"
										title="View Details"
										@click="openDetailModal(item)"
									>
										<span>View</span>
									</Button>

									<!-- Mark as Paid Button (Only for pending) -->
									<Button
										v-if="item.status === 'pending'"
										variant="success"
										size="xs"
										title="Verify and mark as paid"
										@click="openConfirmModal(item)"
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
								</div>
							</td>
						</tr>

						<!-- Empty State Row -->
						<tr v-if="payments.data.length === 0">
							<td colspan="9" class="py-12 p-10">
								<EmptyState
									title="No payments found"
									:description="
										search || status || provider
											? 'No payments matched the selected search criteria.'
											: 'No payment transactions have been recorded yet.'
									"
								>
									<template #icon>
										<svg
											class="h-6 w-6"
											fill="none"
											viewBox="0 0 24 24"
											stroke="currentColor"
										>
											<path
												stroke-linecap="round"
												stroke-linejoin="round"
												stroke-width="1.5"
												d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
											/>
										</svg>
									</template>
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
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Pagination Footer -->
			<div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
				<Pagination
					:links="payments.links"
					:from="payments.from"
					:to="payments.to"
					:total="payments.total"
				/>
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
							variant="active"
							size="md"
							label="Paid"
						/>
						<Badge
							v-else-if="selectedPayment.status === 'pending'"
							variant="pending"
							size="md"
							label="Pending"
						/>
						<Badge
							v-else-if="selectedPayment.status === 'failed'"
							variant="cancelled"
							size="md"
							label="Failed"
						/>
						<Badge
							v-else-if="selectedPayment.status === 'refunded'"
							variant="neutral"
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
								class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 shrink-0 ml-2"
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

				<!-- Metadata Section (if present) -->
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
	</AdminLayout>
</template>
