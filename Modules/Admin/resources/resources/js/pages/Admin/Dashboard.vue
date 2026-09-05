<script setup lang="ts">
interface Tenant {
	public_id: string;
	name: string;
	slug: string;
	industry: string | null;
	status: string;
	country_code: string | null;
	timezone: string;
	locale: string;
	currency: string;
}

defineProps<{
	tenant: Tenant;
}>();
</script>

<template>
	<div class="p-6">
		<div class="mb-6">
			<p class="text-sm text-gray-500">Organization Dashboard</p>

			<h1 class="text-2xl font-semibold text-gray-900">
				{{ tenant.name }}
			</h1>
		</div>

		<div class="grid gap-4 md:grid-cols-3">
			<div class="rounded-xl border bg-white p-5">
				<p class="text-sm text-gray-500">Organization</p>

				<p class="mt-2 text-lg font-semibold">
					{{ tenant.name }}
				</p>
			</div>

			<div class="rounded-xl border bg-white p-5">
				<p class="text-sm text-gray-500">Industry</p>

				<p class="mt-2 text-lg font-semibold">
					{{ tenant.industry ?? "Not specified" }}
				</p>
			</div>

			<div class="rounded-xl border bg-white p-5">
				<p class="text-sm text-gray-500">Currency</p>

				<p class="mt-2 text-lg font-semibold">
					{{ tenant.currency }}
				</p>
			</div>
		</div>
	</div>
</template>