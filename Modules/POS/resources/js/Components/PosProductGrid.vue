<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue';

export interface ProductItem {
    id: number;
    name: string;
    sku: string;
    barcode: string;
    category_id: number;
    category_name: string;
    price: number;
    wholesale_price?: number;
    promo_price?: number | null;
    cost_price?: number;
    current_stock: number;
    is_favorite: boolean;
    track_stock: boolean;
    has_variants: boolean;
    has_modifiers: boolean;
    tax_rate?: number;
    tax_type?: string;
    tax_category_name?: string;
    allow_discount?: boolean;
    max_discount_percentage?: number;
    variants?: Array<{ id: string; name: string; sku: string; barcode: string; price: number; stock: number }>;
    modifier_groups?: Array<any>;
    image?: string | null;
}

export interface CategoryItem {
    id: number | string;
    name: string;
    slug: string;
    icon: string;
    count: number;
}

const props = defineProps<{
    products: ProductItem[];
    categories: CategoryItem[];
    currencySymbol: string;
    priceTier: 'retail' | 'wholesale';
}>();

const emit = defineEmits<{
    (e: 'selectProduct', product: ProductItem): void;
    (e: 'scanBarcode', barcode: string): void;
}>();

const searchQuery = ref('');
const selectedCategoryId = ref<number | string>('all');
const showFavoritesOnly = ref(false);
const barcodeInput = ref('');
const barcodeInputRef = ref<HTMLInputElement | null>(null);

// Category Icons Mapper
const getCategoryIconSvg = (icon: string) => {
    switch (icon) {
        case 'Flame':
            return 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.657 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z';
        case 'Coffee':
            return 'M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8zM6 1v3M10 1v3M14 1v3';
        case 'Utensils':
            return 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253';
        case 'Shirt':
            return 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z';
        case 'Zap':
            return 'M13 10V3L4 14h7v7l9-11h-7z';
        default:
            return 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z';
    }
};

// Filtered items list
const filteredProducts = computed(() => {
    return props.products.filter((item) => {
        // Category Filter
        if (selectedCategoryId.value !== 'all' && item.category_id !== selectedCategoryId.value) {
            return false;
        }
        // Favorites Filter
        if (showFavoritesOnly.value && !item.is_favorite) {
            return false;
        }
        // Search Filter (name, SKU, barcode, category)
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase();
            return (
                item.name.toLowerCase().includes(q) ||
                item.sku.toLowerCase().includes(q) ||
                (item.barcode && item.barcode.toLowerCase().includes(q)) ||
                item.category_name.toLowerCase().includes(q)
            );
        }
        return true;
    });
});

const getEffectivePrice = (item: ProductItem) => {
    if (props.priceTier === 'wholesale' && item.wholesale_price) {
        return item.wholesale_price;
    }
    if (item.promo_price) {
        return item.promo_price;
    }
    return item.price;
};

// Fast Continuous Barcode Scanner Handler
const handleBarcodeSubmit = () => {
    if (!barcodeInput.value.trim()) return;
    emit('scanBarcode', barcodeInput.value.trim());
    barcodeInput.value = '';
    nextTick(() => {
        barcodeInputRef.value?.focus();
    });
};

onMounted(() => {
    barcodeInputRef.value?.focus();
});
</script>

<template>
    <div class="flex-1 flex flex-col h-full bg-slate-100/60 dark:bg-zinc-950 min-w-0 select-none overflow-hidden transition-colors">
        <!-- Top Toolbar: Continuous Barcode Scanner, Search & Fast Filters -->
        <div class="p-3 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 space-y-2.5 shrink-0 transition-colors shadow-2xs">
            <div class="flex items-center gap-2">
                <!-- Continuous Hardware Barcode Scanner Input -->
                <form @submit.prevent="handleBarcodeSubmit" class="relative flex-1 sm:max-w-xs">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-zinc-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                    <input
                        ref="barcodeInputRef"
                        v-model="barcodeInput"
                        type="text"
                        placeholder="Scan barcode (Enter)..."
                        class="w-full pl-8 pr-3 py-2 text-xs font-mono bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700/80 rounded-xl text-zinc-800 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white dark:focus:bg-zinc-950 transition"
                    />
                </form>

                <!-- Keyword Debounced Search Input -->
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-zinc-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search items by name, SKU, category (F2)..."
                        class="w-full pl-8 pr-8 py-2 text-xs bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700/80 rounded-xl text-zinc-800 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white dark:focus:bg-zinc-950 transition"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="searchQuery = ''"
                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Favorites Quick Toggle Filter -->
                <button
                    type="button"
                    @click="showFavoritesOnly = !showFavoritesOnly"
                    :class="[
                        'px-3 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition border cursor-pointer shrink-0',
                        showFavoritesOnly
                            ? 'bg-amber-500 text-white border-amber-500 shadow-xs'
                            : 'bg-slate-50 dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700/80 hover:bg-slate-100 dark:hover:bg-zinc-800'
                    ]"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <span class="hidden sm:inline">Favorites</span>
                </button>
            </div>

            <!-- Categories Horizontal Slider Tabs -->
            <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 scrollbar-none">
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    type="button"
                    @click="selectedCategoryId = cat.id"
                    :class="[
                        'px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition flex items-center gap-1.5 shrink-0 border cursor-pointer',
                        selectedCategoryId === cat.id
                            ? 'bg-emerald-50 dark:bg-emerald-600/20 text-emerald-800 dark:text-emerald-300 border-emerald-300 dark:border-emerald-500/60 shadow-xs'
                            : 'bg-slate-50 dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-200'
                    ]"
                >
                    <svg class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getCategoryIconSvg(cat.icon)" />
                    </svg>
                    <span>{{ cat.name }}</span>
                    <span
                        class="text-[10px] px-1.5 py-0.2 rounded-full font-mono font-medium"
                        :class="selectedCategoryId === cat.id ? 'bg-emerald-200/60 dark:bg-emerald-800/80 text-emerald-900 dark:text-emerald-200' : 'bg-zinc-200/80 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400'"
                    >
                        {{ cat.count }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Product Grid Touch Screen Area -->
        <div class="flex-1 overflow-y-auto p-3 sm:p-4">
            <!-- Empty State -->
            <div
                v-if="filteredProducts.length === 0"
                class="h-full flex flex-col items-center justify-center text-center p-8 text-zinc-500"
            >
                <div class="w-12 h-12 rounded-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center mb-3 text-zinc-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">No items found</h3>
                <p class="text-xs text-zinc-500 mt-1 max-w-xs">
                    Try adjusting your search query, scanning a barcode, or selecting another category.
                </p>
                <button
                    v-if="searchQuery || selectedCategoryId !== 'all' || showFavoritesOnly"
                    @click="searchQuery = ''; selectedCategoryId = 'all'; showFavoritesOnly = false"
                    type="button"
                    class="mt-4 px-3 py-1.5 text-xs bg-white dark:bg-zinc-900 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 rounded-lg border border-zinc-200 dark:border-zinc-700 transition cursor-pointer shadow-xs"
                >
                    Reset Filters
                </button>
            </div>

            <!-- Product Touch Tiles Grid -->
            <div
                v-else
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3"
            >
                <div
                    v-for="item in filteredProducts"
                    :key="item.id"
                    @click="emit('selectProduct', item)"
                    role="button"
                    tabindex="0"
                    class="group relative bg-white dark:bg-zinc-900 hover:bg-emerald-50/40 dark:hover:bg-zinc-800/90 border border-zinc-200 dark:border-zinc-800 hover:border-emerald-400 dark:hover:border-emerald-500/60 rounded-2xl p-3.5 flex flex-col justify-between cursor-pointer transition-all duration-150 transform active:scale-[0.98] shadow-xs hover:shadow-md"
                >
                    <!-- Stock Badge & Variant Tag -->
                    <div class="flex items-start justify-between gap-1 mb-2">
                        <span
                            v-if="item.track_stock"
                            :class="[
                                'text-[10px] font-mono font-bold px-1.5 py-0.5 rounded-md border leading-none',
                                item.current_stock > 20
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/70 dark:text-emerald-400 dark:border-emerald-800/60'
                                    : item.current_stock > 0
                                    ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/70 dark:text-amber-400 dark:border-amber-800/60'
                                    : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/70 dark:text-rose-400 dark:border-rose-800/60'
                            ]"
                        >
                            {{ item.current_stock }} left
                        </span>
                        <span v-else class="text-[10px] font-mono text-zinc-500 px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 leading-none">
                            Non-stock
                        </span>

                        <div class="flex items-center space-x-1">
                            <span
                                v-if="item.has_variants"
                                class="text-[9px] bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60 px-1.5 py-0.5 rounded-md font-medium"
                            >
                                Variants
                            </span>
                            <span
                                v-if="item.has_modifiers"
                                class="text-[9px] bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 px-1.5 py-0.5 rounded-md font-medium"
                            >
                                Modifiers
                            </span>
                        </div>
                    </div>

                    <!-- Product Name & Code -->
                    <div class="my-1">
                        <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-100 line-clamp-2 leading-snug group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors">
                            {{ item.name }}
                        </h4>
                        <div class="text-[10px] font-mono text-zinc-400 dark:text-zinc-500 mt-1 flex items-center justify-between">
                            <span>{{ item.sku }}</span>
                            <span v-if="item.tax_rate !== undefined" class="text-[9px] text-blue-600 dark:text-blue-400 font-bold">
                                {{ item.tax_rate > 0 ? `+${item.tax_rate}% SST` : 'Exempt' }}
                            </span>
                        </div>
                    </div>

                    <!-- Price & Touch Action Button -->
                    <div class="mt-2.5 pt-2 border-t border-zinc-100 dark:border-zinc-800/70 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-extrabold font-mono text-emerald-700 dark:text-emerald-400">
                                {{ currencySymbol }} {{ getEffectivePrice(item).toFixed(2) }}
                            </div>
                            <div v-if="priceTier === 'wholesale' && item.wholesale_price" class="text-[9px] text-zinc-400 line-through">
                                Reg {{ currencySymbol }} {{ item.price.toFixed(2) }}
                            </div>
                            <div v-else-if="item.promo_price" class="text-[9px] text-amber-600 font-bold">
                                Promo (was {{ currencySymbol }}{{ item.price.toFixed(2) }})
                            </div>
                        </div>

                        <div class="w-7 h-7 rounded-xl bg-slate-100 dark:bg-zinc-800 group-hover:bg-emerald-600 group-hover:text-white text-zinc-500 flex items-center justify-center transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
