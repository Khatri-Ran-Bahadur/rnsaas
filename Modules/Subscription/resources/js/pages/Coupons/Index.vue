<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';

interface CouponItem {
    id: number;
    public_id: string;
    code: string;
    name: string;
    discount_type: 'percentage' | 'fixed';
    discount_value: number;
    max_uses: number | null;
    used_count: number;
    expires_at: string | null;
    is_active: boolean;
    is_valid: boolean;
    created_at: string;
}

const props = defineProps<{
    coupons: CouponItem[];
}>();

const searchQuery = ref('');
const statusFilter = ref<'all' | 'active' | 'inactive' | 'expired'>('all');
const copiedCode = ref<string | null>(null);

const isModalOpen = ref(false);
const editingCoupon = ref<CouponItem | null>(null);

const form = useForm({
    code: '',
    name: '',
    discount_type: 'percentage' as 'percentage' | 'fixed',
    discount_value: 10,
    max_uses: null as number | null,
    expires_at: '',
    is_active: true,
});

const filteredCoupons = computed(() => {
    return props.coupons.filter(c => {
        const matchesSearch = c.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            c.name.toLowerCase().includes(searchQuery.value.toLowerCase());

        if (!matchesSearch) return false;

        if (statusFilter.value === 'active') return c.is_active && c.is_valid;
        if (statusFilter.value === 'inactive') return !c.is_active;
        if (statusFilter.value === 'expired') return !c.is_valid && c.is_active;
        return true;
    });
});

const stats = computed(() => {
    const total = props.coupons.length;
    const active = props.coupons.filter(c => c.is_active && c.is_valid).length;
    const totalUses = props.coupons.reduce((acc, c) => acc + c.used_count, 0);
    const expired = props.coupons.filter(c => !c.is_valid).length;
    return { total, active, totalUses, expired };
});

const copyCode = (code: string) => {
    navigator.clipboard.writeText(code);
    copiedCode.value = code;
    setTimeout(() => {
        copiedCode.value = null;
    }, 2000);
};

const openCreateModal = () => {
    editingCoupon.value = null;
    form.reset();
    form.clearErrors();
    form.discount_type = 'percentage';
    form.discount_value = 15;
    form.is_active = true;
    isModalOpen.value = true;
};

const openEditModal = (coupon: CouponItem) => {
    editingCoupon.value = coupon;
    form.clearErrors();
    form.code = coupon.code;
    form.name = coupon.name;
    form.discount_type = coupon.discount_type;
    form.discount_value = coupon.discount_value;
    form.max_uses = coupon.max_uses;
    form.expires_at = coupon.expires_at || '';
    form.is_active = coupon.is_active;
    isModalOpen.value = true;
};

const generateRandomCode = () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let result = 'SAVE';
    for (let i = 0; i < 4; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    form.code = result;
};

const submitForm = () => {
    if (editingCoupon.value) {
        form.put(`/superadmin/subscriptions/coupons/${editingCoupon.value.id}`, {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post('/superadmin/subscriptions/coupons', {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    }
};

const toggleStatus = (coupon: CouponItem) => {
    router.post(`/superadmin/subscriptions/coupons/${coupon.id}/toggle`, {}, {
        preserveScroll: true,
    });
};

const deleteCoupon = (coupon: CouponItem) => {
    if (confirm(`Are you sure you want to delete coupon "${coupon.code}"?`)) {
        router.delete(`/superadmin/subscriptions/coupons/${coupon.id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <SuperAdminLayout title="Subscription Coupons">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-2.5">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                        Subscription Coupons
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Create and manage promotional discount codes for tenant subscription signups and upgrades.
                    </p>
                </div>
                <div>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all duration-150 shadow-indigo-100 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Coupon
                    </button>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Coupons</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total }}</p>
                    </div>
                    <div class="w-11 h-11 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Active & Valid</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ stats.active }}</p>
                    </div>
                    <div class="w-11 h-11 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Redemptions</p>
                        <p class="text-2xl font-bold text-blue-600 mt-1">{{ stats.totalUses }}</p>
                    </div>
                    <div class="w-11 h-11 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Expired / Inactive</p>
                        <p class="text-2xl font-bold text-amber-600 mt-1">{{ stats.expired }}</p>
                    </div>
                    <div class="w-11 h-11 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Bar -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="relative w-full sm:w-80">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by code or campaign name..."
                        class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                    />
                </div>

                <div class="flex items-center gap-1.5 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                    <button
                        v-for="filter in [
                            { id: 'all', label: 'All' },
                            { id: 'active', label: 'Active' },
                            { id: 'expired', label: 'Expired' },
                            { id: 'inactive', label: 'Disabled' }
                        ]"
                        :key="filter.id"
                        @click="statusFilter = filter.id as any"
                        :class="[
                            'px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap cursor-pointer',
                            statusFilter === filter.id
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        ]"
                    >
                        {{ filter.label }}
                    </button>
                </div>
            </div>

            <!-- Coupons Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/75 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5">Coupon Code</th>
                                <th class="px-6 py-3.5">Campaign Name</th>
                                <th class="px-6 py-3.5">Discount</th>
                                <th class="px-6 py-3.5">Usage Limit</th>
                                <th class="px-6 py-3.5">Expiration</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200/75">
                            <tr v-if="filteredCoupons.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                    </svg>
                                    <p class="font-medium text-gray-700">No coupons found</p>
                                    <p class="text-xs text-gray-400 mt-1">Try adjusting your search criteria or create a new coupon code.</p>
                                </td>
                            </tr>
                            <tr
                                v-for="coupon in filteredCoupons"
                                :key="coupon.id"
                                class="hover:bg-gray-50/60 transition-colors"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center font-mono font-bold text-xs px-2.5 py-1 rounded-md bg-indigo-50 border border-indigo-200 text-indigo-700 tracking-wider">
                                            {{ coupon.code }}
                                        </span>
                                        <button
                                            @click="copyCode(coupon.code)"
                                            class="text-gray-400 hover:text-indigo-600 transition-colors cursor-pointer"
                                            title="Copy Code"
                                        >
                                            <svg v-if="copiedCode === coupon.code" class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                    {{ coupon.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="coupon.discount_type === 'percentage'" class="inline-flex items-center gap-1 font-semibold text-emerald-700">
                                        {{ coupon.discount_value }}% OFF
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 font-semibold text-blue-700">
                                        ${{ coupon.discount_value.toFixed(2) }} OFF
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900">{{ coupon.used_count }}</span>
                                        <span class="text-gray-400">/</span>
                                        <span class="text-gray-500">{{ coupon.max_uses ? coupon.max_uses : 'Unlimited' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div v-if="coupon.expires_at" class="flex items-center gap-1.5 text-gray-600 text-xs">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ coupon.expires_at }}
                                    </div>
                                    <span v-else class="text-xs text-gray-400 font-medium">Never Expires</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button
                                        @click="toggleStatus(coupon)"
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition-colors',
                                            coupon.is_active && coupon.is_valid
                                                ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'
                                                : (!coupon.is_valid && coupon.is_active
                                                    ? 'bg-amber-100 text-amber-800'
                                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200')
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'w-1.5 h-1.5 rounded-full',
                                                coupon.is_active && coupon.is_valid ? 'bg-emerald-500' : (!coupon.is_valid && coupon.is_active ? 'bg-amber-500' : 'bg-gray-400')
                                            ]"
                                        />
                                        {{ coupon.is_active && coupon.is_valid ? 'Active' : (!coupon.is_valid && coupon.is_active ? 'Expired' : 'Disabled') }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openEditModal(coupon)"
                                            class="p-1.5 text-gray-400 hover:text-indigo-600 rounded-md hover:bg-indigo-50 transition-colors cursor-pointer"
                                            title="Edit Coupon"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteCoupon(coupon)"
                                            class="p-1.5 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50 transition-colors cursor-pointer"
                                            title="Delete Coupon"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create / Edit Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-200 max-w-lg w-full p-6 animate-in fade-in duration-200">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                            <h3 class="text-lg font-bold text-gray-900">
                                {{ editingCoupon ? 'Edit Coupon' : 'Create New Coupon' }}
                            </h3>
                        </div>
                        <button @click="isModalOpen = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-4 pt-4">
                        <div>
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Coupon Code *</label>
                                <button
                                    type="button"
                                    @click="generateRandomCode"
                                    class="text-xs text-indigo-600 hover:text-indigo-700 font-medium inline-flex items-center gap-1 cursor-pointer"
                                >
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    Generate
                                </button>
                            </div>
                            <input
                                v-model="form.code"
                                type="text"
                                placeholder="e.g. SUMMER50"
                                class="w-full px-3.5 py-2 uppercase font-mono font-semibold border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            />
                            <p v-if="form.errors.code" class="text-xs text-red-600 mt-1">{{ form.errors.code }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Campaign / Name *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="e.g. Summer Launch Special"
                                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            />
                            <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Discount Type *</label>
                                <select
                                    v-model="form.discount_type"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                                >
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount ($)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                    {{ form.discount_type === 'percentage' ? 'Percentage (%)' : 'Amount ($)' }} *
                                </label>
                                <input
                                    v-model.number="form.discount_value"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    required
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Max Usages</label>
                                <input
                                    v-model.number="form.max_uses"
                                    type="number"
                                    placeholder="Unlimited if empty"
                                    min="1"
                                    class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Expiry Date</label>
                                <input
                                    v-model="form.expires_at"
                                    type="date"
                                    class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                />
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input
                                v-model="form.is_active"
                                id="is_active_check"
                                type="checkbox"
                                class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"
                            />
                            <label for="is_active_check" class="text-sm font-medium text-gray-700 select-none">
                                Enable coupon immediately
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                            <button
                                type="button"
                                @click="isModalOpen = false"
                                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-xs transition-colors disabled:opacity-50 cursor-pointer"
                            >
                                {{ editingCoupon ? 'Save Changes' : 'Create Coupon' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
