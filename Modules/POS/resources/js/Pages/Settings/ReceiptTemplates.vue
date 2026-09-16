<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Button } from '@/components';

export interface ReceiptTemplate {
    id: number;
    name: string;
    paper_size: '80mm' | '58mm' | 'a4' | 'a5';
    style: 'modern' | 'classic' | 'restaurant' | 'tax_invoice' | 'boutique';
    locale: 'en' | 'ar' | 'ms' | 'zh' | 'dual_en_ar';
    direction: 'ltr' | 'rtl';
    is_default: boolean;
    header_title: string;
    show_logo: boolean;
    logo_url?: string | null;
    show_tax_number: boolean;
    show_branch: boolean;
    show_cashier: boolean;
    show_customer: boolean;
    show_table: boolean;
    show_sku: boolean;
    show_modifiers: boolean;
    show_discounts: boolean;
    show_tax_summary: boolean;
    show_barcode: boolean;
    show_qr_code: boolean;
    qr_type: 'einvoice' | 'zatca' | 'website' | 'wifi' | 'none';
    footer_notes: string;
}

export interface PrinterItem {
    id: number;
    name: string;
    type: string;
    ip_address: string;
    paper_size: string;
    auto_cut: boolean;
    open_drawer: boolean;
    copies: number;
    status: string;
}

export interface CompanyData {
    name: string;
    reg_no: string;
    tax_id: string;
    address: string;
    phone: string;
    email: string;
    website: string;
}

const props = defineProps<{
    templates: ReceiptTemplate[];
    printers: PrinterItem[];
    company: CompanyData;
}>();

const selectedTemplate = ref<ReceiptTemplate>({ ...props.templates[0] });

// Translations dictionary for dynamic live preview
const translations: Record<string, Record<string, string>> = {
    en: {
        receipt: 'TAX INVOICE',
        receiptNo: 'Receipt #',
        date: 'Date/Time',
        cashier: 'Cashier',
        customer: 'Customer',
        table: 'Table',
        orderType: 'Dine-In',
        item: 'Item',
        qty: 'Qty',
        price: 'Price',
        total: 'Total',
        subtotal: 'Subtotal',
        discount: 'Discount',
        serviceCharge: 'Service Charge (10%)',
        tax: 'Tax (SST 6%)',
        grandTotal: 'GRAND TOTAL',
        paidCash: 'Cash Tendered',
        paidCard: 'Credit Card',
        change: 'Change Due',
        thankYou: 'Thank you for your business! Please come again.',
    },
    ar: {
        receipt: 'فاتورة ضريبية مبسطة',
        receiptNo: 'رقم الفاتورة',
        date: 'التاريخ / الوقت',
        cashier: 'الكاشير',
        customer: 'العميل',
        table: 'الطاولة',
        orderType: 'تناول بالداخل',
        item: 'الصنف',
        qty: 'الكمية',
        price: 'السعر',
        total: 'الإجمالي',
        subtotal: 'المجموع الفرعي',
        discount: 'الخصم',
        serviceCharge: 'رسوم الخدمة (10%)',
        tax: 'ضريبة القيمة المضافة (15%)',
        grandTotal: 'المبلغ الإجمالي',
        paidCash: 'المبلغ المدفوع (نقداً)',
        paidCard: 'بطاقة مصرفية',
        change: 'المبلغ المتبقي',
        thankYou: 'شكراً لزيارتكم ونتطلع لخدمتكم مجدداً!',
    },
    ms: {
        receipt: 'INVOIS CUKAI / RESIT',
        receiptNo: 'No. Resit',
        date: 'Tarikh / Masa',
        cashier: 'Juruwang',
        customer: 'Pelanggan',
        table: 'Meja',
        orderType: 'Makan Di Sini',
        item: 'Item',
        qty: 'Kuantiti',
        price: 'Harga',
        total: 'Jumlah',
        subtotal: 'Jumlah Kecil',
        discount: 'Diskaun',
        serviceCharge: 'Caj Perkhidmatan (10%)',
        tax: 'Cukai Jualan (SST 6%)',
        grandTotal: 'JUMLAH KESELURUHAN',
        paidCash: 'Tunai',
        paidCard: 'Kad Kredit',
        change: 'Baki',
        thankYou: 'Terima kasih atas sokongan anda! Sila datang lagi.',
    },
    zh: {
        receipt: '税务发票 / 结账单',
        receiptNo: '单据号',
        date: '日期/时间',
        cashier: '收银员',
        customer: '顾客',
        table: '桌号',
        orderType: '堂食',
        item: '商品名称',
        qty: '数量',
        price: '单价',
        total: '小计',
        subtotal: '商品总计',
        discount: '折扣优惠',
        serviceCharge: '服务费 (10%)',
        tax: '销售税 (SST 6%)',
        grandTotal: '应付总额',
        paidCash: '现金支付',
        paidCard: '信用卡',
        change: '找零',
        thankYou: '感谢您的惠顾，欢迎再次光临！',
    },
    dual_en_ar: {
        receipt: 'TAX INVOICE / فاتورة ضريبية',
        receiptNo: 'Receipt # / رقم الإيصال',
        date: 'Date / التاريخ',
        cashier: 'Cashier / الكاشير',
        customer: 'Customer / العميل',
        table: 'Table / الطاولة',
        orderType: 'Dine-In / محلي',
        item: 'Item / الصنف',
        qty: 'Qty / الكمية',
        price: 'Price / السعر',
        total: 'Total / الإجمالي',
        subtotal: 'Subtotal / المجموع الفرعي',
        discount: 'Discount / الخصم',
        serviceCharge: 'Service / الخدمة (10%)',
        tax: 'Tax / الضريبة (SST 6%)',
        grandTotal: 'TOTAL / المبلغ الإجمالي',
        paidCash: 'Cash / نقداً',
        paidCard: 'Card / بطاقة',
        change: 'Change / الباقي',
        thankYou: 'Thank you! / شكراً لزيارتكم!',
    },
};

const activeLang = computed(() => {
    return translations[selectedTemplate.value.locale] || translations.en;
});

const isRTL = computed(() => {
    return selectedTemplate.value.direction === 'rtl' || selectedTemplate.value.locale === 'ar';
});

// Switch Active Editing Template
const handleSelectTemplate = (tmpl: ReceiptTemplate) => {
    selectedTemplate.value = { ...tmpl };
};

const handleSave = () => {
    alert(`Template "${selectedTemplate.value.name}" settings saved successfully!`);
};

const handleTestPrint = () => {
    window.print();
};
</script>

<template>
    <Head title="Receipt Templates & Thermal Print Setup - SathiSaaS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/pos" class="hover:text-emerald-600 transition">POS Register</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Receipt Templates & Print Setup</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Universal POS Receipt Designer & Print Setup</span>
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Configure multi-size thermal paper, Arabic RTL / multi-lingual layouts, e-Invoice QR codes, and hardware printing.
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <Link
                        href="/admin/pos/print-settings"
                        class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 transition"
                    >
                        Hardware & Printer Config
                    </Link>
                    <Link
                        href="/admin/pos"
                        class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg transition flex items-center gap-2 cursor-pointer"
                    >
                        <span>Open POS Register</span>
                    </Link>
                </div>
            </div>

            <!-- Templates Selector Pills -->
            <Card class="p-3">
                <div class="flex items-center space-x-2 overflow-x-auto pb-1">
                    <button
                        v-for="tmpl in templates"
                        :key="tmpl.id"
                        type="button"
                        @click="handleSelectTemplate(tmpl)"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 border cursor-pointer shrink-0',
                            selectedTemplate.id === tmpl.id
                                ? 'bg-emerald-50 dark:bg-zinc-800 text-emerald-800 dark:text-emerald-400 border-emerald-300 dark:border-emerald-500/50 shadow-xs'
                                : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                        ]"
                    >
                        <span>{{ tmpl.name }}</span>
                        <span class="text-[10px] bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded-full text-zinc-500 uppercase font-mono">
                            {{ tmpl.paper_size }} • {{ tmpl.locale }}
                        </span>
                        <Badge v-if="tmpl.is_default" variant="success" size="sm">Default</Badge>
                    </button>
                </div>
            </Card>

            <!-- Main Designer Split Workspace (Left: Configurator Controls, Right: Live WYSIWYG Receipt Preview) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left: Form Controls (7 cols) -->
                <div class="lg:col-span-7 space-y-6">
                    <!-- 1. Layout, Paper Size & Locale Section -->
                    <Card class="p-5 space-y-4">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                            <span>1. Paper Size, Style & Localization</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <!-- Template Name -->
                            <div>
                                <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Template Name</label>
                                <input
                                    v-model="selectedTemplate.name"
                                    type="text"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>

                            <!-- Paper Size -->
                            <div>
                                <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Paper Roll / Document Size</label>
                                <select
                                    v-model="selectedTemplate.paper_size"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500 font-mono"
                                >
                                    <option value="80mm">80mm Thermal Paper (Standard POS 48 Col)</option>
                                    <option value="58mm">58mm Thermal Paper (Mobile / Mini 32 Col)</option>
                                    <option value="a4">A4 Full Page Laser / PDF Invoice</option>
                                    <option value="a5">A5 Half Page Slip / Voucher</option>
                                </select>
                            </div>

                            <!-- Design Style -->
                            <div>
                                <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Design Style</label>
                                <select
                                    v-model="selectedTemplate.style"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                >
                                    <option value="modern">Modern Clean (Logo, Sharp Dividers)</option>
                                    <option value="classic">Classic Dot-Matrix (Monospace, Dashed)</option>
                                    <option value="restaurant">Restaurant Dining (Table, Server, Notes)</option>
                                    <option value="tax_invoice">Official Tax Invoice (SST / VAT Breakdown)</option>
                                    <option value="boutique">Elegant Boutique (Serif, Social Links)</option>
                                </select>
                            </div>

                            <!-- Language & Locale -->
                            <div>
                                <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Language & Translation</label>
                                <select
                                    v-model="selectedTemplate.locale"
                                    @change="selectedTemplate.direction = selectedTemplate.locale === 'ar' ? 'rtl' : 'ltr'"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                >
                                    <option value="en">English (LTR)</option>
                                    <option value="ar">Arabic / العربية (RTL)</option>
                                    <option value="ms">Bahasa Melayu (LTR)</option>
                                    <option value="zh">Chinese / 中文 (LTR)</option>
                                    <option value="dual_en_ar">Dual Language (English + Arabic)</option>
                                </select>
                            </div>

                            <!-- Text Direction Toggle -->
                            <div>
                                <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Layout Direction</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        @click="selectedTemplate.direction = 'ltr'"
                                        :class="[
                                            'py-2 rounded-xl font-bold border transition cursor-pointer text-center',
                                            selectedTemplate.direction === 'ltr' ? 'bg-emerald-50 text-emerald-700 border-emerald-400' : 'bg-slate-50 dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800'
                                        ]"
                                    >
                                        LTR (Left-to-Right)
                                    </button>
                                    <button
                                        type="button"
                                        @click="selectedTemplate.direction = 'rtl'"
                                        :class="[
                                            'py-2 rounded-xl font-bold border transition cursor-pointer text-center',
                                            selectedTemplate.direction === 'rtl' ? 'bg-emerald-50 text-emerald-700 border-emerald-400' : 'bg-slate-50 dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800'
                                        ]"
                                    >
                                        RTL (Right-to-Left / عربي)
                                    </button>
                                </div>
                            </div>

                            <!-- Header Document Title -->
                            <div>
                                <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Header Title Banner</label>
                                <input
                                    v-model="selectedTemplate.header_title"
                                    type="text"
                                    placeholder="e.g. TAX INVOICE / فاتورة ضريبية"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>
                        </div>
                    </Card>

                    <!-- 2. Header & Store Info Display Toggles -->
                    <Card class="p-5 space-y-4">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                            <span>2. Header & Store Meta Visibility</span>
                        </h3>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                            <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                                <input type="checkbox" v-model="selectedTemplate.show_logo" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Show Brand Logo</span>
                            </label>
                            <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                                <input type="checkbox" v-model="selectedTemplate.show_tax_number" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Tax / VAT ID</span>
                            </label>
                            <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                                <input type="checkbox" v-model="selectedTemplate.show_branch" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Branch Name</span>
                            </label>
                            <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                                <input type="checkbox" v-model="selectedTemplate.show_cashier" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Cashier Name</span>
                            </label>
                            <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                                <input type="checkbox" v-model="selectedTemplate.show_customer" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Customer Name</span>
                            </label>
                            <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                                <input type="checkbox" v-model="selectedTemplate.show_table" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Table # / Dine Type</span>
                            </label>
                        </div>
                    </Card>

                    <!-- 3. Line Items & Totals Breakdown -->
                    <Card class="p-5 space-y-4">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                            <span>3. Item Columns & Financial Breakdown</span>
                        </h3>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                            <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                                <input type="checkbox" v-model="selectedTemplate.show_sku" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Show Item SKU</span>
                            </label>
                            <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                                <input type="checkbox" v-model="selectedTemplate.show_modifiers" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Show Modifiers & Notes</span>
                            </label>
                            <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                                <input type="checkbox" v-model="selectedTemplate.show_discounts" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Show Discounts</span>
                            </label>
                            <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                                <input type="checkbox" v-model="selectedTemplate.show_tax_summary" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Tax Rate Breakdown</span>
                            </label>
                            <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                                <input type="checkbox" v-model="selectedTemplate.show_barcode" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Return Barcode</span>
                            </label>
                            <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                                <input type="checkbox" v-model="selectedTemplate.show_qr_code" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">e-Invoice QR Code</span>
                            </label>
                        </div>
                    </Card>

                    <!-- 4. Footer Remarks & Return Policy -->
                    <Card class="p-5 space-y-4">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                            <span>4. Receipt Footer & Policy Notes</span>
                        </h3>

                        <div class="text-xs">
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Return Policy / T&C Message</label>
                            <textarea
                                v-model="selectedTemplate.footer_notes"
                                rows="3"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-xs text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-2">
                            <Button variant="secondary" size="sm" @click="handleTestPrint">
                                🖨️ Test Print Preview
                            </Button>
                            <Button variant="primary" size="sm" @click="handleSave">
                                💾 Save Template Settings
                            </Button>
                        </div>
                    </Card>
                </div>

                <!-- Right: Live Interactive Thermal / A4 Preview (5 cols) -->
                <div class="lg:col-span-5 flex flex-col items-center">
                    <div class="w-full mb-2 flex items-center justify-between px-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-500">Live WYSIWYG Print Preview</span>
                        <span class="text-[11px] font-mono text-emerald-600 font-semibold uppercase">
                            {{ selectedTemplate.paper_size }} • {{ selectedTemplate.direction.toUpperCase() }}
                        </span>
                    </div>

                    <!-- Scrollable Receipt Container -->
                    <div class="w-full bg-slate-200/70 dark:bg-zinc-950 p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 flex justify-center overflow-x-auto">
                        <div
                            :dir="isRTL ? 'rtl' : 'ltr'"
                            :class="[
                                'bg-white text-zinc-950 p-6 shadow-xl border border-zinc-300 rounded font-mono text-[11px] leading-tight transition-all',
                                selectedTemplate.paper_size === '80mm' ? 'w-[320px]' : selectedTemplate.paper_size === '58mm' ? 'w-[250px] text-[10px]' : selectedTemplate.paper_size === 'a5' ? 'w-[380px] text-xs' : 'w-[440px] text-xs'
                            ]"
                        >
                            <!-- Header Section -->
                            <div class="text-center space-y-1 pb-3 border-b border-dashed border-zinc-400">
                                <!-- Logo Simulation -->
                                <div v-if="selectedTemplate.show_logo" class="flex justify-center mb-1">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-xl shadow-xs">
                                        S
                                    </div>
                                </div>
                                <div class="text-xs font-black uppercase tracking-wide font-sans text-zinc-900">{{ company.name }}</div>
                                <div class="text-[9px] text-zinc-600 font-sans">Reg No: {{ company.reg_no }}</div>
                                <div v-if="selectedTemplate.show_branch" class="text-[10px] text-zinc-700 font-sans font-medium">Main Store & Headquarters</div>
                                <div class="text-[9px] text-zinc-600">{{ company.address }}</div>
                                <div v-if="selectedTemplate.show_tax_number" class="text-[9px] text-zinc-600">Tax ID: {{ company.tax_id }} • Tel: {{ company.phone }}</div>
                                <div class="pt-1 font-bold text-xs uppercase tracking-wider text-zinc-900 border-t border-zinc-300 mt-1">
                                    {{ selectedTemplate.header_title }}
                                </div>
                            </div>

                            <!-- Meta Details -->
                            <div class="py-2 space-y-1 text-[10px] border-b border-dashed border-zinc-400">
                                <div class="flex justify-between">
                                    <span class="text-zinc-600">{{ activeLang.receiptNo }}:</span>
                                    <span class="font-bold text-zinc-900 font-mono">INV-20260911-0089</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-zinc-600">{{ activeLang.date }}:</span>
                                    <span>2026-09-11 11:30 AM</span>
                                </div>
                                <div v-if="selectedTemplate.show_cashier" class="flex justify-between">
                                    <span class="text-zinc-600">{{ activeLang.cashier }}:</span>
                                    <span>Ran Bahadur (POS-01)</span>
                                </div>
                                <div v-if="selectedTemplate.show_customer" class="flex justify-between">
                                    <span class="text-zinc-600">{{ activeLang.customer }}:</span>
                                    <span class="font-semibold">VIP Corporate Client</span>
                                </div>
                                <div v-if="selectedTemplate.show_table" class="flex justify-between font-bold text-zinc-800">
                                    <span>{{ activeLang.orderType }}:</span>
                                    <span>Table 04 (Indoor Dining)</span>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div class="py-3 border-b border-dashed border-zinc-400 space-y-2">
                                <div class="flex justify-between font-bold text-[10px] uppercase pb-1 border-b border-zinc-300">
                                    <span>{{ activeLang.item }}</span>
                                    <span>{{ activeLang.total }}</span>
                                </div>

                                <!-- Line Item 1 -->
                                <div class="space-y-0.5">
                                    <div class="flex justify-between">
                                        <span class="font-semibold">2x Chicken Deluxe Burger</span>
                                        <span class="font-bold">RM 31.00</span>
                                    </div>
                                    <div v-if="selectedTemplate.show_sku" class="text-[9px] text-zinc-500 font-mono">
                                        SKU: BURGER-001
                                    </div>
                                    <div v-if="selectedTemplate.show_modifiers" class="text-[9px] text-zinc-600 pl-3 rtl:pr-3 rtl:pl-0">
                                        + Extra Cheddar Cheese (RM 2.00)<br />
                                        * Note: "Less spicy sauce"
                                    </div>
                                </div>

                                <!-- Line Item 2 -->
                                <div class="space-y-0.5">
                                    <div class="flex justify-between">
                                        <span class="font-semibold">1x Handcrafted Iced Latte</span>
                                        <span class="font-bold">RM 11.00</span>
                                    </div>
                                    <div v-if="selectedTemplate.show_modifiers" class="text-[9px] text-zinc-600 pl-3 rtl:pr-3 rtl:pl-0">
                                        + Oat Milk Barista (RM 2.50)
                                    </div>
                                </div>
                            </div>

                            <!-- Totals Section -->
                            <div class="py-2.5 space-y-1 border-b border-dashed border-zinc-400 text-[10px]">
                                <div class="flex justify-between">
                                    <span class="text-zinc-600">{{ activeLang.subtotal }}:</span>
                                    <span>RM 42.00</span>
                                </div>
                                <div v-if="selectedTemplate.show_discounts" class="flex justify-between text-emerald-700">
                                    <span>{{ activeLang.discount }} (10% VIP):</span>
                                    <span>-RM 4.20</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-zinc-600">{{ activeLang.serviceCharge }}:</span>
                                    <span>+RM 3.78</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-zinc-600">{{ activeLang.tax }}:</span>
                                    <span>RM 2.51</span>
                                </div>

                                <!-- Large Grand Total -->
                                <div class="pt-1.5 flex justify-between font-black text-xs border-t border-zinc-400 text-zinc-900">
                                    <span>{{ activeLang.grandTotal }}:</span>
                                    <span>RM 44.09</span>
                                </div>
                            </div>

                            <!-- Payments & Change Due -->
                            <div class="py-2 space-y-1 border-b border-dashed border-zinc-400 text-[10px]">
                                <div class="flex justify-between">
                                    <span class="text-zinc-600">{{ activeLang.paidCash }}:</span>
                                    <span>RM 50.00</span>
                                </div>
                                <div class="flex justify-between font-bold text-zinc-900">
                                    <span>{{ activeLang.change }}:</span>
                                    <span>RM 5.91</span>
                                </div>
                            </div>

                            <!-- QR Code & Barcode Section -->
                            <div class="pt-3 text-center space-y-2">
                                <div class="text-[9px] text-zinc-600 leading-tight">
                                    {{ selectedTemplate.footer_notes }}
                                </div>

                                <!-- Simulated e-Invoice QR Code -->
                                <div v-if="selectedTemplate.show_qr_code" class="flex flex-col items-center py-1">
                                    <div class="w-20 h-20 bg-zinc-900 p-1.5 rounded flex items-center justify-center">
                                        <div class="w-full h-full bg-white flex flex-col justify-around p-1">
                                            <div class="flex justify-between">
                                                <div class="w-3 h-3 bg-zinc-900"></div>
                                                <div class="w-3 h-3 bg-zinc-900"></div>
                                            </div>
                                            <div class="text-[6px] font-black tracking-tighter text-zinc-900 text-center">LHDN e-INVOICE</div>
                                            <div class="flex justify-between">
                                                <div class="w-3 h-3 bg-zinc-900"></div>
                                                <div class="w-3 h-3 bg-zinc-900"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-[8px] text-zinc-500 mt-0.5">Scan for Tax Authority Verification</span>
                                </div>

                                <!-- Simulated Return Barcode -->
                                <div v-if="selectedTemplate.show_barcode" class="pt-1">
                                    <div class="font-mono text-xs tracking-widest font-bold py-1 bg-zinc-100 rounded border border-zinc-200">
                                        *INV-20260911-0089*
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
