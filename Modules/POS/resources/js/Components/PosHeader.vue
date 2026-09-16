<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ThemeToggle from '@/components/ThemeToggle.vue';

const props = defineProps<{
    terminal: {
        id: number;
        name: string;
        branch_name: string;
        company_name: string;
        currency_symbol: string;
    };
    currentShift: {
        id: number;
        shift_number: string;
        cashier_name: string;
        cashier_role: string;
        status: string;
        expected_cash: number;
    };
    activeMode: 'retail' | 'restaurant' | 'wholesale';
    heldOrdersCount: number;
    pendingSyncCount: number;
    isOnline: boolean;
}>();


const page = usePage();
const isHospitality = computed(() => {
    const tenant = (page.props as any).current_tenant || (page.props as any).currentTenant;
    if (!tenant) return true;
    if (typeof tenant.is_hospitality !== 'undefined') return Boolean(tenant.is_hospitality);
    const ind = String(tenant.industry || '').toLowerCase();
    return ['restaurant', 'food_beverage', 'hospitality', 'hotel'].includes(ind);
});

const emit = defineEmits<{
    (e: 'update:activeMode', mode: 'retail' | 'restaurant' | 'wholesale'): void;
    (e: 'openShift'): void;
    (e: 'openHeldOrders'): void;
    (e: 'openCustomerDisplay'): void;
    (e: 'openCustomItem'): void;
    (e: 'toggleFullscreen'): void;
    (e: 'triggerSync'): void;
}>();

const currentTime = ref(new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
let timerInterval: any = null;

onMounted(() => {
    timerInterval = setInterval(() => {
        currentTime.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }, 1000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

const isFullscreen = ref(false);

const handleToggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
        isFullscreen.value = true;
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen().catch(() => {});
            isFullscreen.value = false;
        }
    }
    emit('toggleFullscreen');
};
</script>

<template>
    <header class="bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 border-b border-zinc-200 dark:border-zinc-800 px-4 py-2.5 flex items-center justify-between select-none shadow-xs shrink-0 transition-colors">
        <!-- Left: Branding, Branch & Terminal Info -->
        <div class="flex items-center space-x-3">
            <Link href="/admin/dashboard" class="flex items-center space-x-2.5 group">
                <div class="w-8 h-8 rounded-xl bg-emerald-600 flex items-center justify-center font-bold text-white shadow-sm group-hover:bg-emerald-500 transition-colors">
                    S
                </div>
                <div>
                    <div class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5 leading-none">
                        <span>{{ terminal.branch_name }}</span>
                        <span class="text-zinc-400 dark:text-zinc-600">•</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-mono text-[11px] font-semibold">{{ terminal.name }}</span>
                    </div>
                    <div class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-tight mt-0.5">
                        {{ terminal.company_name }}
                    </div>
                </div>
            </Link>

            <div class="h-6 w-px bg-zinc-200 dark:bg-zinc-800 hidden sm:block"></div>

            <!-- Operational Mode Switcher -->
            <div class="hidden md:flex items-center bg-zinc-100 dark:bg-zinc-800 p-0.5 rounded-xl border border-zinc-200/80 dark:border-zinc-700/60 text-xs">
                <button
                    type="button"
                    @click="emit('update:activeMode', 'retail')"
                    :class="[
                        'px-3 py-1 rounded-lg font-medium transition-all flex items-center gap-1.5 cursor-pointer',
                        activeMode === 'retail' ? 'bg-white dark:bg-zinc-900 text-emerald-700 dark:text-emerald-400 font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                    ]"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Retail / Counter
                </button>
                <button
                    v-if="isHospitality"
                    type="button"
                    @click="emit('update:activeMode', 'restaurant')"
                    :class="[
                        'px-3 py-1 rounded-lg font-medium transition-all flex items-center gap-1.5 cursor-pointer',
                        activeMode === 'restaurant' ? 'bg-white dark:bg-zinc-900 text-amber-700 dark:text-amber-400 font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                    ]"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    Restaurant & F&B
                </button>
                <button
                    type="button"
                    @click="emit('update:activeMode', 'wholesale')"
                    :class="[
                        'px-3 py-1 rounded-lg font-medium transition-all flex items-center gap-1.5 cursor-pointer',
                        activeMode === 'wholesale' ? 'bg-white dark:bg-zinc-900 text-blue-700 dark:text-blue-400 font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                    ]"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Wholesale
                </button>
            </div>
        </div>

        <!-- Center: Quick Actions & Mode-specific navigation -->
        <div class="flex items-center space-x-2">
            <!-- Custom Item Quick Entry -->
            <button
                type="button"
                @click="emit('openCustomItem')"
                class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition flex items-center gap-1.5 border border-emerald-200 dark:border-emerald-800/60 cursor-pointer"
                title="Add Custom / Open Price Item"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Custom Item</span>
            </button>

            <!-- Table Floor Plan (Restaurant Mode) -->
            <Link
                v-if="activeMode === 'restaurant'"
                href="/admin/pos/floor-plan"
                class="px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 hover:text-zinc-900 dark:hover:text-white transition flex items-center gap-1.5 border border-zinc-200 dark:border-zinc-700"
            >
                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                <span>Floor Plan</span>
            </Link>

            <!-- KDS (Restaurant Mode) -->
            <Link
                v-if="activeMode === 'restaurant'"
                href="/admin/pos/kds"
                class="px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 hover:text-zinc-900 dark:hover:text-white transition flex items-center gap-1.5 border border-zinc-200 dark:border-zinc-700"
            >
                <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                <span>Kitchen KDS</span>
            </Link>

            <!-- Sales History Link -->
            <Link
                href="/admin/pos/orders"
                class="px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 hover:text-zinc-900 dark:hover:text-white transition flex items-center gap-1.5 border border-zinc-200 dark:border-zinc-700"
            >
                <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Orders</span>
            </Link>

            <!-- Held Orders Drawer Trigger -->
            <button
                type="button"
                @click="emit('openHeldOrders')"
                class="relative px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 hover:text-zinc-900 dark:hover:text-white transition flex items-center gap-1.5 border border-zinc-200 dark:border-zinc-700 cursor-pointer"
            >
                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Held</span>
                <span
                    v-if="heldOrdersCount > 0"
                    class="px-1.5 py-0.2 text-[10px] font-bold rounded-full bg-amber-500 text-white ml-0.5 animate-pulse"
                >
                    {{ heldOrdersCount }}
                </span>
            </button>
        </div>

        <!-- Right: Shift status, Hardware indicators, Sync & Cashier info -->
        <div class="flex items-center space-x-2.5">
            <!-- Offline & Sync Status -->
            <button
                type="button"
                @click="emit('triggerSync')"
                class="flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/60 hover:bg-zinc-200 dark:hover:bg-zinc-800 transition cursor-pointer"
                :title="isOnline ? 'System Online (Click to force sync)' : 'Offline mode active (Orders queued locally)'"
            >
                <span class="w-2 h-2 rounded-full" :class="isOnline ? 'bg-emerald-500 shadow-[0_0_6px_rgba(16,185,129,0.7)]' : 'bg-amber-500 shadow-[0_0_6px_rgba(245,158,11,0.7)]'"></span>
                <span class="text-zinc-700 dark:text-zinc-300 text-[11px] font-medium hidden sm:inline">
                    {{ isOnline ? 'Online' : 'Offline' }}
                </span>
                <span v-if="pendingSyncCount > 0" class="text-[10px] bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300 px-1 rounded font-mono font-bold">
                    {{ pendingSyncCount }}
                </span>
            </button>

            <!-- Hardware Status Indicators -->
            <div class="hidden lg:flex items-center space-x-1.5 text-zinc-500 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-800/50 px-2 py-1 rounded-lg border border-zinc-200 dark:border-zinc-800 text-[11px]">
                <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400" title="Thermal Receipt Printer (80mm) Connected">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>80mm</span>
                </span>
                <span class="text-zinc-300 dark:text-zinc-700">•</span>
                <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400" title="USB Barcode Scanner Ready">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span>Scanner</span>
                </span>
            </div>

            <!-- Shift & Cashier Button -->
            <button
                type="button"
                @click="emit('openShift')"
                class="flex items-center space-x-2 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700 px-2.5 py-1 rounded-lg text-left transition cursor-pointer"
            >
                <div class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-600/30 text-emerald-700 dark:text-emerald-400 flex items-center justify-center text-xs font-bold border border-emerald-300 dark:border-emerald-500/40">
                    {{ currentShift.cashier_name.charAt(0) }}
                </div>
                <div class="hidden xl:block">
                    <div class="text-[11px] font-bold text-zinc-900 dark:text-zinc-200 leading-tight">
                        {{ currentShift.cashier_name }}
                    </div>
                    <div class="text-[10px] text-emerald-700 dark:text-emerald-400 font-mono leading-tight">
                        Shift #{{ currentShift.shift_number }}
                    </div>
                </div>
            </button>

            <!-- Customer Facing Display Simulator Trigger -->
            <button
                type="button"
                @click="emit('openCustomerDisplay')"
                class="p-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                title="Open Dual Customer-Facing Display"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </button>

            <!-- Theme Toggle -->
            <ThemeToggle />

            <!-- Fullscreen Button -->
            <button
                type="button"
                @click="handleToggleFullscreen"
                class="p-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                :title="isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen POS Mode'"
            >
                <svg v-if="!isFullscreen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <!-- Clock -->
            <div class="hidden sm:block text-xs font-mono text-zinc-500 dark:text-zinc-400 pl-1">
                {{ currentTime }}
            </div>
        </div>
    </header>
</template>
