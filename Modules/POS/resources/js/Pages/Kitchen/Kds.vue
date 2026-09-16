<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge } from '@/components';

export interface TicketItem {
    id: string;
    order_number: string;
    table_name: string;
    order_type: string;
    server_name: string;
    created_at: string;
    elapsed_minutes: number;
    priority: 'normal' | 'rush' | 'vip';
    status: 'new' | 'preparing' | 'ready' | 'served';
    station: 'kitchen' | 'bar' | 'grill';
    items: Array<{
        name: string;
        variant?: string;
        quantity: number;
        modifiers: string[];
        notes?: string;
        done?: boolean;
    }>;
}

export interface StationItem {
    id: string;
    name: string;
}

const props = defineProps<{
    stations: StationItem[];
    tickets: TicketItem[];
    currentStation: string;
}>();

const selectedStation = ref(props.currentStation || 'all');
const liveTickets = ref<TicketItem[]>([...props.tickets]);

const filteredTickets = () => {
    if (selectedStation.value === 'all') return liveTickets.value;
    return liveTickets.value.filter((t) => t.station === selectedStation.value);
};

const handleAdvanceTicketStatus = (ticket: TicketItem) => {
    let nextStatus: TicketItem['status'] = 'preparing';
    if (ticket.status === 'new') {
        nextStatus = 'preparing';
    } else if (ticket.status === 'preparing') {
        nextStatus = 'ready';
    } else if (ticket.status === 'ready') {
        nextStatus = 'served';
    }

    ticket.status = nextStatus;
    if (nextStatus === 'served') {
        liveTickets.value = liveTickets.value.filter((t) => t.id !== ticket.id);
    }

    router.post('/admin/pos/kds/update-status', {
        id: ticket.id,
        status: nextStatus,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const getStatusBadgeVariant = (status: TicketItem['status']) => {
    switch (status) {
        case 'new': return 'info';
        case 'preparing': return 'warning';
        case 'ready': return 'success';
        default: return 'secondary';
    }
};
</script>

<template>
    <Head title="Kitchen Display System (KDS) - POS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header & Station Selector -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/pos" class="hover:text-emerald-600 transition">POS Register</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Kitchen Display</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Kitchen Display System (KDS)</span>
                    </h1>
                </div>

                <div class="flex items-center space-x-3">
                    <Link
                        href="/admin/pos/floor-plan"
                        class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 transition"
                    >
                        Floor Plan
                    </Link>
                    <Link
                        href="/admin/pos"
                        class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg transition flex items-center gap-2"
                    >
                        <span>Open POS Register</span>
                    </Link>
                </div>
            </div>

            <!-- Station Filters -->
            <Card class="p-3">
                <div class="flex items-center space-x-2 overflow-x-auto">
                    <button
                        v-for="st in stations"
                        :key="st.id"
                        type="button"
                        @click="selectedStation = st.id"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 border cursor-pointer',
                            selectedStation === st.id
                                ? 'bg-emerald-50 dark:bg-zinc-800 text-emerald-800 dark:text-emerald-400 border-emerald-300 dark:border-emerald-500/50 shadow-xs'
                                : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                        ]"
                    >
                        <span>{{ st.name }}</span>
                    </button>
                </div>
            </Card>

            <!-- KDS Live Tickets Column / Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div
                    v-for="ticket in filteredTickets()"
                    :key="ticket.id"
                    class="bg-white dark:bg-zinc-900 rounded-2xl border flex flex-col justify-between shadow-sm overflow-hidden transition-all"
                    :class="[
                        ticket.elapsed_minutes > 15
                            ? 'border-rose-400 dark:border-rose-700/80 ring-1 ring-rose-400'
                            : ticket.priority === 'rush'
                            ? 'border-amber-400 dark:border-amber-700/80 ring-1 ring-amber-400'
                            : 'border-zinc-200 dark:border-zinc-800'
                    ]"
                >
                    <!-- Ticket Header -->
                    <div class="p-3.5 bg-slate-50 dark:bg-zinc-950/80 border-b border-zinc-200 dark:border-zinc-800 flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-extrabold text-sm text-zinc-900 dark:text-white font-mono">{{ ticket.order_number }}</span>
                                <Badge :variant="getStatusBadgeVariant(ticket.status)" class="uppercase text-[9px]">
                                    {{ ticket.status }}
                                </Badge>
                            </div>
                            <div class="text-xs font-bold text-zinc-800 dark:text-zinc-200 mt-1">
                                {{ ticket.table_name }} • <span class="uppercase text-zinc-500 font-sans font-normal">{{ ticket.order_type }}</span>
                            </div>
                        </div>

                        <div class="text-right">
                            <div
                                class="text-xs font-mono font-bold px-2 py-0.5 rounded-md"
                                :class="ticket.elapsed_minutes > 15 ? 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-400 animate-pulse' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400'"
                            >
                                {{ ticket.elapsed_minutes }}m ago
                            </div>
                            <div class="text-[10px] text-zinc-400 mt-0.5">Server: {{ ticket.server_name }}</div>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="p-4 flex-1 space-y-3 overflow-y-auto max-h-72 divide-y divide-zinc-100 dark:divide-zinc-800/60">
                        <div
                            v-for="(item, idx) in ticket.items"
                            :key="idx"
                            class="pt-2 first:pt-0"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-zinc-100 dark:bg-zinc-800 font-mono font-bold text-xs flex items-center justify-center text-zinc-800 dark:text-zinc-200">
                                        {{ item.quantity }}
                                    </span>
                                    <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ item.name }}</span>
                                </div>
                                <span v-if="item.variant" class="text-[10px] font-mono text-blue-600 dark:text-blue-400">
                                    {{ item.variant }}
                                </span>
                            </div>

                            <!-- Modifiers & Cooking Notes -->
                            <div v-if="item.modifiers && item.modifiers.length > 0" class="pl-8 text-[11px] text-zinc-500 space-y-0.5 mt-0.5">
                                <div v-for="(m, mIdx) in item.modifiers" :key="mIdx">+ {{ m }}</div>
                            </div>

                            <div v-if="item.notes" class="pl-8 text-[11px] text-amber-600 dark:text-amber-400 italic font-medium mt-0.5">
                                Note: "{{ item.notes }}"
                            </div>
                        </div>
                    </div>

                    <!-- Ticket Bottom Action -->
                    <div class="p-3 bg-slate-50 dark:bg-zinc-950/80 border-t border-zinc-200 dark:border-zinc-800">
                        <button
                            type="button"
                            @click="handleAdvanceTicketStatus(ticket)"
                            class="w-full py-2.5 rounded-xl font-bold text-xs shadow transition flex items-center justify-center gap-2 cursor-pointer"
                            :class="[
                                ticket.status === 'new'
                                    ? 'bg-blue-600 hover:bg-blue-500 text-white'
                                    : ticket.status === 'preparing'
                                    ? 'bg-amber-600 hover:bg-amber-500 text-white'
                                    : 'bg-emerald-600 hover:bg-emerald-500 text-white'
                            ]"
                        >
                            <span v-if="ticket.status === 'new'">Start Preparing →</span>
                            <span v-else-if="ticket.status === 'preparing'">Mark Ready for Pickup ✓</span>
                            <span v-else>Mark Served & Complete 🚀</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
