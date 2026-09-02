<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";

const form = useForm({
	email: "",
	password: "",
	remember: false,
});

const submit = () => {
	form.post("/login", {
		onFinish: () => {
			form.reset("password");
		},
	});
};
</script>

<template>
	<Head title="Log In" />

	<div
		class="flex min-h-screen items-center justify-center bg-zinc-50 p-4 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100"
	>
		<div
			class="w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-8 shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
		>
			<!-- Brand Logo / Header -->
			<div class="mb-8 text-center">
				<div
					class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-900 font-bold text-lg text-white shadow-xs dark:bg-white dark:text-zinc-900"
				>
					S
				</div>
				<h1
					class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white"
				>
					Sign in to SathiSaaS
				</h1>
				<p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">
					Enter your credentials to access the platform.
				</p>
			</div>

			<!-- Login Form -->
			<form class="space-y-5" @submit.prevent="submit">
				<!-- Email -->
				<div>
					<label
						for="email"
						class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300"
					>
						Email address
					</label>
					<div class="mt-1.5">
						<input
							id="email"
							v-model="form.email"
							type="email"
							autocomplete="username"
							required
							autofocus
							placeholder="admin@sathisaas.com"
							class="w-full rounded-lg border border-zinc-200 bg-white py-2.5 px-3.5 text-sm text-zinc-900 placeholder-zinc-400 transition-colors focus:border-zinc-900 focus:outline-hidden focus:ring-1 focus:ring-zinc-900 dark:border-zinc-800 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500"
							:class="{
								'border-rose-500 focus:border-rose-500 focus:ring-rose-500':
									form.errors.email,
							}"
						/>
					</div>
					<p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-500">
						{{ form.errors.email }}
					</p>
				</div>

				<!-- Password -->
				<div>
					<div class="flex items-center justify-between">
						<label
							for="password"
							class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300"
						>
							Password
						</label>
					</div>
					<div class="mt-1.5">
						<input
							id="password"
							v-model="form.password"
							type="password"
							autocomplete="current-password"
							required
							placeholder="••••••••"
							class="w-full rounded-lg border border-zinc-200 bg-white py-2.5 px-3.5 text-sm text-zinc-900 placeholder-zinc-400 transition-colors focus:border-zinc-900 focus:outline-hidden focus:ring-1 focus:ring-zinc-900 dark:border-zinc-800 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500"
							:class="{
								'border-rose-500 focus:border-rose-500 focus:ring-rose-500':
									form.errors.password,
							}"
						/>
					</div>
					<p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-500">
						{{ form.errors.password }}
					</p>
				</div>

				<!-- Remember Me -->
				<div class="flex items-center">
					<label class="flex items-center gap-2 cursor-pointer">
						<input
							v-model="form.remember"
							type="checkbox"
							class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:focus:ring-offset-zinc-900"
						/>
						<span class="text-sm text-zinc-600 dark:text-zinc-400"
							>Remember me</span
						>
					</label>
				</div>

				<!-- Submit Button -->
				<button
					type="submit"
					:disabled="form.processing"
					class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-zinc-900 py-2.5 px-4 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-zinc-800 focus:outline-hidden focus:ring-2 focus:ring-zinc-900 disabled:opacity-50 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100"
				>
					<svg
						v-if="form.processing"
						class="h-4 w-4 animate-spin text-white dark:text-zinc-900"
						xmlns="http://www.w3.org/2000/svg"
						fill="none"
						viewBox="0 0 24 24"
					>
						<circle
							class="opacity-25"
							cx="12"
							cy="12"
							r="10"
							stroke="currentColor"
							stroke-width="4"
						/>
						<path
							class="opacity-75"
							fill="currentColor"
							d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
						/>
					</svg>
					<span>{{ form.processing ? "Signing in..." : "Sign in" }}</span>
				</button>
			</form>
		</div>
	</div>
</template>
