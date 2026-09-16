<script setup lang="ts">
import { ref, computed } from 'vue';

export interface ReceiptData {
    receiptNumber: string;
    date: string;
    branchName: string;
    companyName: string;
    taxNumber: string;
    address: string;
    phone: string;
    cashierName: string;
    terminalName: string;
    customerName: string;
    orderType: string;
    tableNumber?: string | null;
    items: Array<{
        name: string;
        variant_name?: string;
        quantity: number;
        unit_price: number;
        total: number;
        modifiers?: Array<{ name: string; price: number }>;
    }>;
    subtotal: number;
    discountTotal: number;
    taxTotal: number;
    serviceChargeTotal?: number;
    grandTotal: number;
    payments: Array<{ method_name: string; amount: number; reference?: string }>;
    changeDue: number;
    currencySymbol: string;
}

const props = defineProps<{
    show: boolean;
    receipt: ReceiptData | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const format = ref<'80mm' | '58mm' | 'a4' | 'a5'>('80mm');
const selectedLocale = ref<'en' | 'ar' | 'ms' | 'zh' | 'dual_en_ar'>('en');

const translations: Record<string, Record<string, string>> = {
    en: {
        receiptTitle: 'TAX INVOICE / RECEIPT',
        receiptNo: 'Receipt #',
        date: 'Date/Time',
        cashier: 'Cashier',
        customer: 'Customer',
        table: 'Table',
        item: 'Item',
        qty: 'Qty',
        price: 'Price',
        total: 'Total',
        subtotal: 'Subtotal',
        discount: 'Discount',
        serviceCharge: 'Service Charge (10%)',
        tax: 'Tax (SST 6%)',
        grandTotal: 'GRAND TOTAL',
        paid: 'Tendered',
        change: 'Change Due',
        thankYou: 'Thank you for your business! Please visit again.',
    },
    ar: {
        receiptTitle: 'فاتورة ضريبية مبسطة',
        receiptNo: 'رقم الفاتورة',
        date: 'التاريخ / الوقت',
        cashier: 'الكاشير',
        customer: 'العميل',
        table: 'الطاولة',
        item: 'الصنف',
        qty: 'الكمية',
        price: 'السعر',
        total: 'الإجمالي',
        subtotal: 'المجموع الفرعي',
        discount: 'الخصم',
        serviceCharge: 'رسوم الخدمة (10%)',
        tax: 'ضريبة القيمة المضافة (15%)',
        grandTotal: 'المبلغ الإجمالي',
        paid: 'المدفوع',
        change: 'المتبقي',
        thankYou: 'شكراً لتعاملكم معنا ونتطلع لخدمتكم مجدداً!',
    },
    ms: {
        receiptTitle: 'INVOIS CUKAI / RESIT JUALAN',
        receiptNo: 'No. Resit',
        date: 'Tarikh/Masa',
        cashier: 'Juruwang',
        customer: 'Pelanggan',
        table: 'Meja',
        item: 'Item',
        qty: 'Kuantiti',
        price: 'Harga',
        total: 'Jumlah',
        subtotal: 'Jumlah Kecil',
        discount: 'Diskaun',
        serviceCharge: 'Caj Perkhidmatan (10%)',
        tax: 'Cukai Jualan (SST 6%)',
        grandTotal: 'JUMLAH KESELURUHAN',
        paid: 'Bayaran Diterima',
        change: 'Baki',
        thankYou: 'Terima kasih atas sokongan anda! Sila datang lagi.',
    },
    zh: {
        receiptTitle: '税务发票 / 销售小票',
        receiptNo: '单据号',
        date: '开单时间',
        cashier: '收银员',
        customer: '顾客',
        table: '桌号',
        item: '商品名称',
        qty: '数量',
        price: '单价',
        total: '小计',
        subtotal: '商品总额',
        discount: '折扣优惠',
        serviceCharge: '服务费 (10%)',
        tax: '销售税 (SST 6%)',
        grandTotal: '实付总额',
        paid: '实收金额',
        change: '找零',
        thankYou: '感谢您的惠顾，欢迎再次光临！',
    },
    dual_en_ar: {
        receiptTitle: 'TAX INVOICE / فاتورة ضريبية',
        receiptNo: 'Receipt # / رقم الإيصال',
        date: 'Date / التاريخ',
        cashier: 'Cashier / الكاشير',
        customer: 'Customer / العميل',
        table: 'Table / الطاولة',
        item: 'Item / الصنف',
        qty: 'Qty / الكمية',
        price: 'Price / السعر',
        total: 'Total / الإجمالي',
        subtotal: 'Subtotal / المجموع الفرعي',
        discount: 'Discount / الخصم',
        serviceCharge: 'Service / الخدمة (10%)',
        tax: 'Tax / الضريبة (SST 6%)',
        grandTotal: 'TOTAL / المبلغ الإجمالي',
        paid: 'Paid / المدفوع',
        change: 'Change / الباقي',
        thankYou: 'Thank you! / شكراً لزيارتكم!',
    },
};

const t = computed(() => {
    return translations[selectedLocale.value] || translations.en;
});

const isRTL = computed(() => {
    return selectedLocale.value === 'ar';
});

const handlePrint = () => {
    window.print();
};
</script>

<template>
    <div
        v-if="show && receipt"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 dark:bg-black/80 backdrop-blur-xs select-none"
        @keydown.esc="emit('close')"
    >
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-xl overflow-hidden shadow-2xl flex flex-col max-h-[94vh]">
            <!-- Header with Format, Locale & RTL Controls -->
            <div class="px-4 py-3 bg-slate-50 dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center space-x-2 text-xs">
                    <!-- Paper Size Switcher -->
                    <div class="flex bg-zinc-200/80 dark:bg-zinc-800 p-0.5 rounded-lg text-[11px]">
                        <button
                            type="button"
                            @click="format = '80mm'"
                            :class="['px-2 py-0.5 rounded-md font-medium transition cursor-pointer', format === '80mm' ? 'bg-white dark:bg-zinc-900 text-emerald-700 dark:text-emerald-400 font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400']"
                        >
                            80mm
                        </button>
                        <button
                            type="button"
                            @click="format = '58mm'"
                            :class="['px-2 py-0.5 rounded-md font-medium transition cursor-pointer', format === '58mm' ? 'bg-white dark:bg-zinc-900 text-emerald-700 dark:text-emerald-400 font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400']"
                        >
                            58mm
                        </button>
                        <button
                            type="button"
                            @click="format = 'a4'"
                            :class="['px-2 py-0.5 rounded-md font-medium transition cursor-pointer', format === 'a4' ? 'bg-white dark:bg-zinc-900 text-emerald-700 dark:text-emerald-400 font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400']"
                        >
                            A4
                        </button>
                    </div>

                    <!-- Language Switcher -->
                    <select
                        v-model="selectedLocale"
                        class="text-[11px] font-semibold bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg px-2 py-1 text-zinc-800 dark:text-zinc-200"
                    >
                        <option value="en">English (LTR)</option>
                        <option value="ar">Arabic / العربية (RTL)</option>
                        <option value="ms">Bahasa Melayu</option>
                        <option value="zh">Chinese / 中文</option>
                        <option value="dual_en_ar">Dual EN / AR</option>
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <button
                        type="button"
                        @click="handlePrint"
                        class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow flex items-center gap-1.5 transition cursor-pointer"
                    >
                        <span>🖨️ Print</span>
                    </button>
                    <button
                        type="button"
                        @click="emit('close')"
                        class="p-1 rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <!-- Receipt Canvas / Printable Thermal Layout -->
            <div class="flex-1 overflow-y-auto p-6 bg-slate-100 dark:bg-zinc-950/80 flex justify-center">
                <div
                    id="pos-receipt-print-area"
                    :dir="isRTL ? 'rtl' : 'ltr'"
                    :class="[
                        'bg-white text-zinc-950 p-6 shadow-md border border-zinc-200 rounded font-mono text-[11px] leading-tight transition-all',
                        format === '80mm' ? 'w-[320px]' : format === '58mm' ? 'w-[250px] text-[10px]' : 'w-[440px] text-xs'
                    ]"
                >
                    <!-- Receipt Header -->
                    <div class="text-center space-y-1 pb-3 border-b border-dashed border-zinc-400">
                        <div class="text-xs font-black uppercase tracking-wide font-sans text-zinc-900">{{ receipt.companyName }}</div>
                        <div class="text-[10px] text-zinc-600 font-sans">{{ receipt.branchName }}</div>
                        <div class="text-[9px] text-zinc-600">{{ receipt.address }}</div>
                        <div class="text-[9px] text-zinc-600">Tax ID: {{ receipt.taxNumber }} • Tel: {{ receipt.phone }}</div>
                        <div class="pt-1 font-bold text-xs uppercase tracking-wider text-zinc-900 border-t border-zinc-300 mt-1">
                            {{ t.receiptTitle }}
                        </div>
                    </div>

                    <!-- Meta Details -->
                    <div class="py-2 space-y-1 text-[10px] border-b border-dashed border-zinc-400">
                        <div class="flex justify-between">
                            <span class="text-zinc-600">{{ t.receiptNo }}:</span>
                            <span class="font-bold text-zinc-900 font-mono">{{ receipt.receiptNumber }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600">{{ t.date }}:</span>
                            <span>{{ receipt.date }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600">{{ t.cashier }}:</span>
                            <span>{{ receipt.cashierName }} ({{ receipt.terminalName }})</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600">{{ t.customer }}:</span>
                            <span class="font-semibold">{{ receipt.customerName }}</span>
                        </div>
                        <div v-if="receipt.tableNumber" class="flex justify-between font-bold text-zinc-800">
                            <span>{{ t.table }}:</span>
                            <span>{{ receipt.tableNumber }} ({{ receipt.orderType }})</span>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="py-3 border-b border-dashed border-zinc-400 space-y-2">
                        <div class="flex justify-between font-bold text-[10px] uppercase pb-1 border-b border-zinc-300">
                            <span>{{ t.item }}</span>
                            <span>{{ t.total }}</span>
                        </div>

                        <div
                            v-for="(item, idx) in receipt.items"
                            :key="idx"
                            class="space-y-0.5"
                        >
                            <div class="flex justify-between">
                                <span class="font-semibold">{{ item.quantity }}x {{ item.name }}</span>
                                <span class="font-bold">{{ receipt.currencySymbol }} {{ item.total.toFixed(2) }}</span>
                            </div>
                            <div v-if="item.variant_name" class="text-[9px] text-zinc-500 pl-2 rtl:pr-2 rtl:pl-0">
                                [{{ item.variant_name }}]
                            </div>
                            <div
                                v-if="item.modifiers && item.modifiers.length > 0"
                                class="text-[9px] text-zinc-600 pl-3 rtl:pr-3 rtl:pl-0 space-y-0.5"
                            >
                                <div v-for="(m, mIdx) in item.modifiers" :key="mIdx">
                                    + {{ m.name }} ({{ receipt.currencySymbol }}{{ m.price.toFixed(2) }})
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financials Breakdown -->
                    <div class="py-2.5 space-y-1 border-b border-dashed border-zinc-400 text-[10px]">
                        <div class="flex justify-between">
                            <span class="text-zinc-600">{{ t.subtotal }}:</span>
                            <span>{{ receipt.currencySymbol }} {{ receipt.subtotal.toFixed(2) }}</span>
                        </div>
                        <div v-if="receipt.discountTotal > 0" class="flex justify-between text-emerald-700 font-semibold">
                            <span>{{ t.discount }}:</span>
                            <span>-{{ receipt.currencySymbol }} {{ receipt.discountTotal.toFixed(2) }}</span>
                        </div>
                        <div v-if="receipt.serviceChargeTotal && receipt.serviceChargeTotal > 0" class="flex justify-between">
                            <span class="text-zinc-600">{{ t.serviceCharge }}:</span>
                            <span>+{{ receipt.currencySymbol }} {{ receipt.serviceChargeTotal.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600">{{ t.tax }}:</span>
                            <span>{{ receipt.currencySymbol }} {{ receipt.taxTotal.toFixed(2) }}</span>
                        </div>

                        <!-- Grand Total -->
                        <div class="pt-1.5 flex justify-between font-black text-xs border-t border-zinc-400 text-zinc-900">
                            <span>{{ t.grandTotal }}:</span>
                            <span>{{ receipt.currencySymbol }} {{ receipt.grandTotal.toFixed(2) }}</span>
                        </div>
                    </div>

                    <!-- Payments & Change -->
                    <div class="py-2 space-y-1 border-b border-dashed border-zinc-400 text-[10px]">
                        <div
                            v-for="(pay, pIdx) in receipt.payments"
                            :key="pIdx"
                            class="flex justify-between"
                        >
                            <span class="text-zinc-600">{{ pay.method_name }}:</span>
                            <span>{{ receipt.currencySymbol }} {{ pay.amount.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-zinc-900">
                            <span>{{ t.change }}:</span>
                            <span>{{ receipt.currencySymbol }} {{ receipt.changeDue.toFixed(2) }}</span>
                        </div>
                    </div>

                    <!-- Footer & e-Invoice QR -->
                    <div class="pt-3 text-center space-y-2">
                        <div class="text-[9px] text-zinc-600 leading-tight">
                            {{ t.thankYou }}
                        </div>

                        <!-- e-Invoice QR Code -->
                        <div class="flex flex-col items-center py-1">
                            <div class="w-16 h-16 bg-zinc-900 p-1 rounded flex items-center justify-center">
                                <div class="w-full h-full bg-white flex flex-col justify-around p-0.5">
                                    <div class="flex justify-between">
                                        <div class="w-2.5 h-2.5 bg-zinc-900"></div>
                                        <div class="w-2.5 h-2.5 bg-zinc-900"></div>
                                    </div>
                                    <div class="text-[5px] font-black tracking-tighter text-zinc-900 text-center">e-INVOICE</div>
                                    <div class="flex justify-between">
                                        <div class="w-2.5 h-2.5 bg-zinc-900"></div>
                                        <div class="w-2.5 h-2.5 bg-zinc-900"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Return Barcode -->
                        <div class="pt-1">
                            <div class="font-mono text-[10px] tracking-widest font-bold py-0.5 bg-zinc-100 rounded border border-zinc-200">
                                *{{ receipt.receiptNumber }}*
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    body * {
        visibility: hidden;
    }
    #pos-receipt-print-area, #pos-receipt-print-area * {
        visibility: visible;
    }
    #pos-receipt-print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 8px;
        box-shadow: none;
        border: none;
    }
}
</style>
