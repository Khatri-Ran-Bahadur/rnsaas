<script setup lang="ts">
interface TaxRateOption {
    id: number | string;
    name: string;
}

const props = defineProps<{
    form: {
        name: string;
        priority: number | string;
        transaction_type: 'sales' | 'purchases' | 'both';
        sales_channel: string;
        customer_type: string;
        item_type: string;
        applied_tax_rate_id: number | string | '';
        tax_inclusive_mode: boolean;
        is_active: boolean;
        description: string;
    };
    taxRates?: TaxRateOption[];
}>();
</script>

<template>
    <div class="space-y-5">
        <!-- Top: Rule Title & Evaluation Priority -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-200 mb-1.5">
                    Rule Title <span class="text-rose-500">*</span>
                </label>
                <input
                    v-model="form.name"
                    type="text"
                    placeholder="e.g. International B2B Export Zero-Rated Override"
                    required
                    class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-white transition shadow-sm"
                />
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-200 mb-1.5">
                    Evaluation Priority <span class="text-rose-500">*</span>
                </label>
                <input
                    v-model.number="form.priority"
                    type="number"
                    min="1"
                    placeholder="10"
                    required
                    class="w-full px-3.5 py-2.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-white font-mono transition shadow-sm"
                />
                <p class="text-[11px] text-zinc-400 mt-1">Lower numbers evaluate first (e.g. 10 before 50)</p>
            </div>
        </div>

        <!-- Section: Matching Conditions & Dimensions -->
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700/80 bg-zinc-50/60 dark:bg-zinc-800/30 p-4 sm:p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-xs font-black text-zinc-900 dark:text-white uppercase tracking-wider">
                        Matching Conditions & Dimensions
                    </h4>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Transactions matching all selected dimensions below will trigger this rule.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Transaction Scope -->
                <div class="bg-white dark:bg-zinc-800 p-3.5 rounded-xl border border-zinc-200/80 dark:border-zinc-700 shadow-sm space-y-1.5">
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300">
                        Transaction Scope
                    </label>
                    <select
                        v-model="form.transaction_type"
                        class="w-full px-3 py-2 text-xs font-medium bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-zinc-800 dark:text-zinc-200"
                    >
                        <option value="sales">Sales (Outward Invoices / POS Register)</option>
                        <option value="purchases">Purchases (Inward Vendor Bills)</option>
                        <option value="both">Both Sales & Purchases</option>
                    </select>
                </div>

                <!-- Customer / Entity Type -->
                <div class="bg-white dark:bg-zinc-800 p-3.5 rounded-xl border border-zinc-200/80 dark:border-zinc-700 shadow-sm space-y-1.5">
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300">
                        Customer / Entity Type
                    </label>
                    <select
                        v-model="form.customer_type"
                        class="w-full px-3 py-2 text-xs font-medium bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-zinc-800 dark:text-zinc-200"
                    >
                        <option value="all">Any Customer / Entity Type</option>
                        <option value="b2c_individual">B2C Retail / Individual Consumer</option>
                        <option value="b2b_registered">B2B Tax-Registered Enterprise</option>
                        <option value="international_export">International Cross-Border / Export</option>
                        <option value="government_body">Government Body / Tax Exempt</option>
                        <option value="non_resident_vendor">Non-Resident Foreign Vendor</option>
                    </select>
                </div>

                <!-- Sales / Order Channel -->
                <div class="bg-white dark:bg-zinc-800 p-3.5 rounded-xl border border-zinc-200/80 dark:border-zinc-700 shadow-sm space-y-1.5">
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300">
                        Sales / Order Channel
                    </label>
                    <select
                        v-model="form.sales_channel"
                        class="w-full px-3 py-2 text-xs font-medium bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-zinc-800 dark:text-zinc-200"
                    >
                        <option value="all">All Channels (Omnichannel)</option>
                        <option value="pos">POS Touch Register Only</option>
                        <option value="accounting_invoice">Accounting Tax Invoices</option>
                        <option value="restaurant">Restaurant Dine-in / KDS</option>
                        <option value="ecommerce">eCommerce Online Orders</option>
                        <option value="wholesale">B2B Wholesale Dispatch</option>
                    </select>
                </div>

                <!-- Product / Service Scope -->
                <div class="bg-white dark:bg-zinc-800 p-3.5 rounded-xl border border-zinc-200/80 dark:border-zinc-700 shadow-sm space-y-1.5">
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300">
                        Product / Service Scope
                    </label>
                    <select
                        v-model="form.item_type"
                        class="w-full px-3 py-2 text-xs font-medium bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-zinc-800 dark:text-zinc-200"
                    >
                        <option value="all">All Products & Billable Services</option>
                        <option value="goods">Physical Inventory Goods Only</option>
                        <option value="services">Professional / Billable Services</option>
                        <option value="digital_services">Digital Goods & Software Subscriptions</option>
                        <option value="restaurant_dish">Prepared Food & Beverage Items</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section: Action (Assigned Tax Rate & Mode) -->
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700/80 bg-zinc-50/60 dark:bg-zinc-800/30 p-4 sm:p-5 space-y-4">
            <div>
                <h4 class="text-xs font-black text-zinc-900 dark:text-white uppercase tracking-wider">
                    Action: Assigned Tax Rate & Pricing Mode
                </h4>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                    Define the tax rate to automatically apply when the above conditions are met.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Applied Tax Rate -->
                <div class="bg-white dark:bg-zinc-800 p-3.5 rounded-xl border border-zinc-200/80 dark:border-zinc-700 shadow-sm space-y-1.5">
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300">
                        Applied Tax Rate <span class="text-rose-500">*</span>
                    </label>
                    <select
                        v-model="form.applied_tax_rate_id"
                        required
                        class="w-full px-3 py-2.5 text-xs font-medium bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-zinc-800 dark:text-zinc-200"
                    >
                        <option value="">Select tax rate to apply...</option>
                        <option v-for="r in taxRates" :key="r.id" :value="r.id">
                            {{ r.name }}
                        </option>
                    </select>
                </div>

                <!-- Price Quotation Mode Toggle Card -->
                <div class="bg-white dark:bg-zinc-800 p-3.5 rounded-xl border border-zinc-200/80 dark:border-zinc-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">Price Quotation Mode</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.tax_inclusive_mode"
                                    class="sr-only peer"
                                />
                                <div class="w-10 h-5.5 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-4.5 after:w-4.5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-2">
                            {{ form.tax_inclusive_mode ? 'Tax-Inclusive: Product price already includes tax amount.' : 'Tax-Exclusive: Tax is added on top of invoice subtotal.' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Audit Description -->
            <div class="bg-white dark:bg-zinc-800 p-3.5 rounded-xl border border-zinc-200/80 dark:border-zinc-700 shadow-sm space-y-1.5">
                <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300">
                    Audit Description & Reference Notes
                </label>
                <input
                    v-model="form.description"
                    type="text"
                    placeholder="Brief explanation of why this tax rule is configured (e.g. Statutory exemption per Section 12)..."
                    class="w-full px-3 py-2 text-xs bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-zinc-800 dark:text-zinc-200"
                />
            </div>
        </div>
    </div>
</template>
