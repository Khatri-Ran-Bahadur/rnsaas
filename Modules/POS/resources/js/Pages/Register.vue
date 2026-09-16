<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import PosHeader from '../Components/PosHeader.vue';
import PosProductGrid, { type ProductItem, type CategoryItem } from '../Components/PosProductGrid.vue';
import PosCart, { type CartLineItem, type Customer } from '../Components/PosCart.vue';
import PosPaymentModal, { type PaymentMethod, type SplitPaymentLine } from '../Components/PosPaymentModal.vue';
import PosModifierModal from '../Components/PosModifierModal.vue';
import PosShiftModal, { type ShiftData } from '../Components/PosShiftModal.vue';
import PosReceiptModal, { type ReceiptData } from '../Components/PosReceiptModal.vue';
import PosHoldOrdersDrawer, { type HeldOrder } from '../Components/PosHoldOrdersDrawer.vue';
import PosVoidModal from '../Components/PosVoidModal.vue';
import PosCustomerDisplayModal from '../Components/PosCustomerDisplayModal.vue';
import PosQuickCustomerModal from '../Components/PosQuickCustomerModal.vue';
import PosCustomItemModal from '../Components/PosCustomItemModal.vue';
import PosLineEditorModal from '../Components/PosLineEditorModal.vue';
import { Modal } from '@/components';

const props = defineProps<{
    terminal: {
        id: number;
        name: string;
        branch_name: string;
        company_name: string;
        tax_number: string;
        address: string;
        phone: string;
        currency_symbol: string;
    };
    currentShift: ShiftData;
    categories: CategoryItem[];
    products: ProductItem[];
    customers: Customer[];
    paymentMethods: PaymentMethod[];
    heldOrders: HeldOrder[];
    defaultTaxRate: number;
    taxInclusive: boolean;
    initialTable?: string | null;
}>();

// App State
const activeMode = ref<'retail' | 'restaurant' | 'wholesale'>('retail');
const orderType = ref<'dine_in' | 'takeaway' | 'delivery'>(props.initialTable ? 'dine_in' : 'takeaway');
const selectedTable = ref<string | null>(props.initialTable || null);
const selectedCustomer = ref<Customer>(props.customers[0] || {
    id: 1,
    name: 'Walk-in Customer',
    phone: 'N/A',
    customer_group: 'Retail Walk-in',
    price_tier: 'retail',
    discount_rate: 0,
    outstanding_balance: 0,
    credit_limit: 0,
});

const cartItems = ref<CartLineItem[]>([]);
const orderDiscount = ref<{ type: 'percent' | 'fixed'; value: number }>({ type: 'percent', value: 0 });
const heldOrdersList = ref<HeldOrder[]>([...props.heldOrders]);
const pendingSyncCount = ref(0);
const isOnline = ref(navigator.onLine);

// Modals visibility state
const showPaymentModal = ref(false);
const showModifierModal = ref(false);
const showShiftModal = ref(false);
const showReceiptModal = ref(false);
const showHoldDrawer = ref(false);
const showVoidModal = ref(false);
const showCustomerDisplayModal = ref(false);
const showCustomerSelectModal = ref(false);
const showNewCustomerModal = ref(false);
const showCustomItemModal = ref(false);
const showLineEditorModal = ref(false);
const showDiscountModal = ref(false);

const activeConfiguringProduct = ref<ProductItem | null>(null);
const activeEditingLineItem = ref<CartLineItem | null>(null);
const lineEditorMode = ref<'price' | 'notes' | 'all'>('all');
const activeReceiptData = ref<ReceiptData | null>(null);
const lastPaymentChange = ref<number | null>(null);

// Temporary Discount Modal State
const tempDiscountType = ref<'percent' | 'fixed'>('percent');
const tempDiscountValue = ref<number>(0);

// Online / Offline Listeners
const updateOnlineStatus = () => {
    isOnline.value = navigator.onLine;
};

onMounted(() => {
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    window.addEventListener('keydown', handleGlobalKeyboardShortcuts);
});

onBeforeUnmount(() => {
    window.removeEventListener('online', updateOnlineStatus);
    window.removeEventListener('offline', updateOnlineStatus);
    window.removeEventListener('keydown', handleGlobalKeyboardShortcuts);
});

// Keyboard Shortcuts (F2: Search, F4: Pay, Escape: Close Modals)
const handleGlobalKeyboardShortcuts = (e: KeyboardEvent) => {
    if (e.key === 'F4') {
        e.preventDefault();
        if (cartItems.value.length > 0 && !showPaymentModal.value) {
            handleOpenPayment();
        }
    }
};

// Pricing Tier
const currentPriceTier = computed(() => {
    if (activeMode.value === 'wholesale') return 'wholesale';
    return selectedCustomer.value.price_tier === 'wholesale' ? 'wholesale' : 'retail';
});

// Financial Calculations for Cart
const calculatedSubtotal = computed(() => {
    return cartItems.value.reduce((sum, item) => {
        const modifierAddons = item.modifiers.reduce((mSum, m) => mSum + m.price, 0);
        const lineTotal = ((item.unit_price + modifierAddons) * item.quantity) - item.discount_amount;
        return sum + Math.max(0, lineTotal);
    }, 0);
});

const calculatedDiscountTotal = computed(() => {
    let disc = 0;
    if (orderDiscount.value.type === 'percent') {
        disc = (calculatedSubtotal.value * orderDiscount.value.value) / 100;
    } else {
        disc = orderDiscount.value.value;
    }
    if (selectedCustomer.value.discount_rate > 0 && orderDiscount.value.value === 0) {
        disc = (calculatedSubtotal.value * selectedCustomer.value.discount_rate) / 100;
    }
    return Math.min(calculatedSubtotal.value, disc);
});

const calculatedTaxableAmount = computed(() => {
    return Math.max(0, calculatedSubtotal.value - calculatedDiscountTotal.value);
});

const calculatedTaxTotal = computed(() => {
    if (props.taxInclusive) {
        return (calculatedTaxableAmount.value * props.defaultTaxRate) / (100 + props.defaultTaxRate);
    } else {
        return (calculatedTaxableAmount.value * props.defaultTaxRate) / 100;
    }
});

const calculatedServiceCharge = computed(() => {
    if (orderType.value === 'dine_in') {
        return calculatedTaxableAmount.value * 0.10;
    }
    return 0;
});

const calculatedGrandTotal = computed(() => {
    if (props.taxInclusive) {
        return calculatedTaxableAmount.value + calculatedServiceCharge.value;
    } else {
        return calculatedTaxableAmount.value + calculatedTaxTotal.value + calculatedServiceCharge.value;
    }
});

// Product Selection & Barcode Scanning Handling
const handleSelectProduct = (product: ProductItem) => {
    if (product.has_variants || product.has_modifiers) {
        activeConfiguringProduct.value = product;
        showModifierModal.value = true;
        return;
    }

    const price = (currentPriceTier.value === 'wholesale' && product.wholesale_price)
        ? product.wholesale_price
        : product.price;

    const existingLine = cartItems.value.find(
        (i) => i.product_id === product.id && (!i.modifiers || i.modifiers.length === 0) && !i.variant_id
    );

    if (existingLine) {
        existingLine.quantity += 1;
    } else {
        cartItems.value.push({
            id: `line_${Date.now()}_${Math.random().toString(36).substr(2, 5)}`,
            product_id: product.id,
            name: product.name,
            sku: product.sku,
            unit_price: price,
            quantity: 1,
            discount_amount: 0,
            discount_percent: 0,
            tax_rate: props.defaultTaxRate,
            modifiers: [],
        });
    }
};

const handleScanBarcode = (scannedCode: string) => {
    const matched = props.products.find(
        (p) => p.barcode === scannedCode || p.sku.toLowerCase() === scannedCode.toLowerCase()
    );
    if (matched) {
        handleSelectProduct(matched);
    } else {
        alert(`No product found matching barcode "${scannedCode}".`);
    }
};

const handleConfirmModifiers = (payload: {
    product: ProductItem;
    selectedVariant: any | null;
    selectedModifiers: Array<{ id: string; name: string; price: number }>;
    quantity: number;
    notes: string;
    calculatedPrice: number;
}) => {
    cartItems.value.push({
        id: `line_${Date.now()}_${Math.random().toString(36).substr(2, 5)}`,
        product_id: payload.product.id,
        name: payload.product.name,
        sku: payload.selectedVariant ? payload.selectedVariant.sku : payload.product.sku,
        variant_id: payload.selectedVariant?.id,
        variant_name: payload.selectedVariant?.name,
        unit_price: payload.calculatedPrice,
        quantity: payload.quantity,
        discount_amount: 0,
        discount_percent: 0,
        tax_rate: props.defaultTaxRate,
        modifiers: payload.selectedModifiers,
        notes: payload.notes,
    });
    showModifierModal.value = false;
};

// Cart Item Mutators
const handleUpdateQuantity = ({ lineId, delta }: { lineId: string; delta: number }) => {
    const item = cartItems.value.find((i) => i.id === lineId);
    if (item) {
        item.quantity += delta;
        if (item.quantity <= 0) {
            handleRemoveLine(lineId);
        }
    }
};

const handleRemoveLine = (lineId: string) => {
    cartItems.value = cartItems.value.filter((i) => i.id !== lineId);
};

const handleEditLineNotes = (lineId: string) => {
    const item = cartItems.value.find((i) => i.id === lineId);
    if (item) {
        activeEditingLineItem.value = item;
        lineEditorMode.value = 'notes';
        showLineEditorModal.value = true;
    }
};

const handleEditLinePrice = (lineId: string) => {
    const item = cartItems.value.find((i) => i.id === lineId);
    if (item) {
        activeEditingLineItem.value = item;
        lineEditorMode.value = 'price';
        showLineEditorModal.value = true;
    }
};

const handleSaveLineModifications = (payload: { lineId: string; unitPrice: number; notes: string }) => {
    const item = cartItems.value.find((i) => i.id === payload.lineId);
    if (item) {
        item.unit_price = payload.unitPrice;
        item.notes = payload.notes;
    }
    showLineEditorModal.value = false;
};

// Custom / Open Item Creation
const handleAddCustomItem = (payload: {
    name: string;
    price: number;
    quantity: number;
    tax_rate: number;
    notes: string;
}) => {
    cartItems.value.push({
        id: `custom_${Date.now()}`,
        product_id: 999999,
        name: payload.name,
        sku: 'CUSTOM-ITEM',
        unit_price: payload.price,
        quantity: payload.quantity,
        discount_amount: 0,
        discount_percent: 0,
        tax_rate: payload.tax_rate,
        modifiers: [],
        notes: payload.notes,
    });
    showCustomItemModal.value = false;
};

// Customer Creation & Selection
const handleCustomerCreated = (customer: Customer) => {
    selectedCustomer.value = customer;
    showNewCustomerModal.value = false;
};

// Hold & Resume Orders
const handleHoldOrder = () => {
    if (cartItems.value.length === 0) return;
    const newHeld: HeldOrder = {
        id: `HOLD-${Date.now().toString().slice(-6)}`,
        held_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        customer_name: selectedCustomer.value.name,
        table_number: selectedTable.value || (orderType.value === 'takeaway' ? 'Takeaway' : 'Delivery'),
        order_type: orderType.value,
        items_count: cartItems.value.reduce((acc, i) => acc + i.quantity, 0),
        total_amount: calculatedGrandTotal.value,
        cart_items: [...cartItems.value],
    };
    heldOrdersList.value.unshift(newHeld);
    cartItems.value = [];
    orderDiscount.value = { type: 'percent', value: 0 };
};

const handleResumeHeldOrder = (order: HeldOrder) => {
    cartItems.value = [...order.cart_items];
    orderType.value = order.order_type as any;
    selectedTable.value = order.table_number.startsWith('Table') ? order.table_number : null;
    heldOrdersList.value = heldOrdersList.value.filter((h) => h.id !== order.id);
    showHoldDrawer.value = false;
};

const handleDiscardHeldOrder = (orderId: string) => {
    heldOrdersList.value = heldOrdersList.value.filter((h) => h.id !== orderId);
};

// Void / Clear Cart with Security PIN
const handleOpenVoidModal = () => {
    if (cartItems.value.length === 0) return;
    showVoidModal.value = true;
};

const handleConfirmVoid = () => {
    cartItems.value = [];
    orderDiscount.value = { type: 'percent', value: 0 };
    showVoidModal.value = false;
};

// Discount Modal
const handleOpenDiscountModal = () => {
    tempDiscountType.value = orderDiscount.value.type;
    tempDiscountValue.value = orderDiscount.value.value;
    showDiscountModal.value = true;
};

const handleApplyOrderDiscount = () => {
    orderDiscount.value = {
        type: tempDiscountType.value,
        value: Number(tempDiscountValue.value) || 0,
    };
    showDiscountModal.value = false;
};

// Payment & Quick Cash Tender
const handleOpenPayment = () => {
    showPaymentModal.value = true;
};

const handleQuickCashPayment = (tenderAmount: number) => {
    const finalPayments: SplitPaymentLine[] = [
        {
            method_id: 'cash',
            method_name: 'Cash',
            amount: tenderAmount,
        },
    ];
    const change = Math.max(0, tenderAmount - calculatedGrandTotal.value);
    handleCompleteSale({
        payments: finalPayments,
        changeAmount: change,
        printReceipt: true,
    });
};

const handleCompleteSale = (payload: { payments: SplitPaymentLine[]; changeAmount: number; printReceipt: boolean }) => {
    lastPaymentChange.value = payload.changeAmount;

    // Construct Receipt Snapshot
    activeReceiptData.value = {
        receiptNumber: `INV-${Date.now().toString().slice(-8)}`,
        date: new Date().toLocaleString(),
        branchName: props.terminal.branch_name,
        companyName: props.terminal.company_name,
        taxNumber: props.terminal.tax_number,
        address: props.terminal.address,
        phone: props.terminal.phone,
        cashierName: props.currentShift.cashier_name,
        terminalName: props.terminal.name,
        customerName: selectedCustomer.value.name,
        orderType: orderType.value,
        tableNumber: selectedTable.value,
        items: cartItems.value.map((item) => ({
            name: item.name,
            variant_name: item.variant_name,
            quantity: item.quantity,
            unit_price: item.unit_price,
            total: (item.unit_price * item.quantity) - item.discount_amount,
            modifiers: item.modifiers,
        })),
        subtotal: calculatedSubtotal.value,
        discountTotal: calculatedDiscountTotal.value,
        taxTotal: calculatedTaxTotal.value,
        serviceChargeTotal: calculatedServiceCharge.value,
        grandTotal: calculatedGrandTotal.value,
        payments: payload.payments,
        changeDue: payload.changeAmount,
        currencySymbol: props.terminal.currency_symbol,
    };

    // Save to Database via backend
    router.post('/admin/pos/checkout', {
        customer_name: selectedCustomer.value.name,
        customer_phone: selectedCustomer.value.phone,
        order_type: orderType.value,
        table_name: selectedTable.value,
        subtotal: calculatedSubtotal.value,
        tax_total: calculatedTaxTotal.value,
        discount_total: calculatedDiscountTotal.value,
        grand_total: calculatedGrandTotal.value,
        paid_amount: payload.payments.reduce((acc, p) => acc + (p.amount || 0), 0),
        change_amount: payload.changeAmount,
        payment_method: payload.payments.map((p) => p.method_name).join(' + ') || 'Cash',
        items: cartItems.value.map((item) => ({
            name: item.name,
            quantity: item.quantity,
            unit_price: item.unit_price,
            discount: item.discount_amount,
            total: (item.unit_price * item.quantity) - item.discount_amount,
            modifiers: item.modifiers,
            notes: item.notes,
        })),
    }, {
        preserveScroll: true,
        preserveState: true,
    });

    // Reset Active Cart
    cartItems.value = [];
    orderDiscount.value = { type: 'percent', value: 0 };
    showPaymentModal.value = false;

    if (payload.printReceipt) {
        showReceiptModal.value = true;
    }
};

// Shifts & Cash movements
const handleRecordCashMovement = (payload: { type: 'cash_in' | 'cash_out'; amount: number; reason: string }) => {
    if (payload.type === 'cash_in') {
        props.currentShift.cash_in += payload.amount;
        props.currentShift.expected_cash += payload.amount;
    } else {
        props.currentShift.cash_out += payload.amount;
        props.currentShift.expected_cash -= payload.amount;
    }
};

const handleCloseShift = (payload: { actualCash: number; variance: number; notes: string }) => {
    showShiftModal.value = false;
    router.visit('/admin/pos/shifts');
};
</script>

<template>
    <Head title="POS Register - SathiSaaS" />

    <div class="h-screen w-screen flex flex-col bg-slate-50 dark:bg-zinc-950 text-zinc-800 dark:text-zinc-100 overflow-hidden font-sans select-none transition-colors">
        <!-- 1. Top POS Operational Header -->
        <PosHeader
            :terminal="terminal"
            :currentShift="currentShift"
            v-model:activeMode="activeMode"
            :heldOrdersCount="heldOrdersList.length"
            :pendingSyncCount="pendingSyncCount"
            :isOnline="isOnline"
            @openShift="showShiftModal = true"
            @openHeldOrders="showHoldDrawer = true"
            @openCustomerDisplay="showCustomerDisplayModal = true"
            @openCustomItem="showCustomItemModal = true"
            @triggerSync="pendingSyncCount = 0"
        />

        <!-- 2. Main Operational Workspace (Split Screen: Catalog & Cart) -->
        <div class="flex-1 flex flex-col lg:flex-row overflow-hidden min-h-0">
            <!-- Left Side: Interactive Search & Product Touch Grid -->
            <PosProductGrid
                :products="products"
                :categories="categories"
                :currencySymbol="terminal.currency_symbol"
                :priceTier="currentPriceTier"
                @selectProduct="handleSelectProduct"
                @scanBarcode="handleScanBarcode"
            />

            <!-- Right Side: Active Cart & Fast Checkout Footer -->
            <PosCart
                :cartItems="cartItems"
                :customers="customers"
                v-model:selectedCustomer="selectedCustomer"
                v-model:orderType="orderType"
                :selectedTable="selectedTable"
                :currencySymbol="terminal.currency_symbol"
                :orderDiscount="orderDiscount"
                :taxInclusive="taxInclusive"
                :defaultTaxRate="defaultTaxRate"
                @updateQuantity="handleUpdateQuantity"
                @removeLine="handleRemoveLine"
                @editLineNotes="handleEditLineNotes"
                @editLinePrice="handleEditLinePrice"
                @openOrderDiscountModal="handleOpenDiscountModal"
                @holdOrder="handleHoldOrder"
                @clearCart="handleOpenVoidModal"
                @proceedPayment="handleOpenPayment"
                @quickCashPayment="handleQuickCashPayment"
                @openCustomerSelectModal="showCustomerSelectModal = true"
                @openNewCustomerModal="showNewCustomerModal = true"
            />
        </div>

        <!-- 3. Operational Modals & Drawers -->

        <!-- Multi-tender Split Payment Modal -->
        <PosPaymentModal
            :show="showPaymentModal"
            :grandTotal="calculatedGrandTotal"
            :subtotal="calculatedSubtotal"
            :discountTotal="calculatedDiscountTotal"
            :taxTotal="calculatedTaxTotal"
            :currencySymbol="terminal.currency_symbol"
            :paymentMethods="paymentMethods"
            :customerName="selectedCustomer.name"
            @close="showPaymentModal = false"
            @completePayment="handleCompleteSale"
        />

        <!-- Product Variants & Modifiers Configurator -->
        <PosModifierModal
            :show="showModifierModal"
            :product="activeConfiguringProduct"
            :currencySymbol="terminal.currency_symbol"
            @close="showModifierModal = false"
            @confirm="handleConfirmModifiers"
        />

        <!-- Cashier Shift & Cash Drawer Reconciliation Modal -->
        <PosShiftModal
            :show="showShiftModal"
            :shift="currentShift"
            :currencySymbol="terminal.currency_symbol"
            @close="showShiftModal = false"
            @recordCashMovement="handleRecordCashMovement"
            @closeShift="handleCloseShift"
        />

        <!-- Thermal Receipt Print Preview Modal -->
        <PosReceiptModal
            :show="showReceiptModal"
            :receipt="activeReceiptData"
            @close="showReceiptModal = false"
        />

        <!-- Parked / Held Orders Drawer -->
        <PosHoldOrdersDrawer
            :show="showHoldDrawer"
            :heldOrders="heldOrdersList"
            :currencySymbol="terminal.currency_symbol"
            @close="showHoldDrawer = false"
            @resumeOrder="handleResumeHeldOrder"
            @discardOrder="handleDiscardHeldOrder"
        />

        <!-- Manager Security Void Confirmation Modal -->
        <PosVoidModal
            :show="showVoidModal"
            @close="showVoidModal = false"
            @confirmVoid="handleConfirmVoid"
        />

        <!-- Customer-Facing Dual Screen Display -->
        <PosCustomerDisplayModal
            :show="showCustomerDisplayModal"
            :companyName="terminal.company_name"
            :branchName="terminal.branch_name"
            :customer="selectedCustomer"
            :cartItems="cartItems"
            :subtotal="calculatedSubtotal"
            :discountTotal="calculatedDiscountTotal"
            :taxTotal="calculatedTaxTotal"
            :grandTotal="calculatedGrandTotal"
            :currencySymbol="terminal.currency_symbol"
            :lastPaymentChange="lastPaymentChange"
            @close="showCustomerDisplayModal = false"
        />

        <!-- Quick Add Customer Modal -->
        <PosQuickCustomerModal
            :show="showNewCustomerModal"
            @close="showNewCustomerModal = false"
            @customerCreated="handleCustomerCreated"
        />

        <!-- Custom / Open Price Item Modal -->
        <PosCustomItemModal
            :show="showCustomItemModal"
            :currencySymbol="terminal.currency_symbol"
            :defaultTaxRate="defaultTaxRate"
            @close="showCustomItemModal = false"
            @addCustomItem="handleAddCustomItem"
        />

        <!-- Line Price & Notes Editor Modal -->
        <PosLineEditorModal
            :show="showLineEditorModal"
            :lineItem="activeEditingLineItem"
            :currencySymbol="terminal.currency_symbol"
            :mode="lineEditorMode"
            @close="showLineEditorModal = false"
            @saveLine="handleSaveLineModifications"
        />

        <!-- Customer Selector Modal -->
        <Modal :show="showCustomerSelectModal" @close="showCustomerSelectModal = false">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-200 dark:border-zinc-800">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Select Customer</h3>
                    <button
                        type="button"
                        @click="showCustomerSelectModal = false; showNewCustomerModal = true"
                        class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold hover:underline cursor-pointer"
                    >
                        + Add New
                    </button>
                </div>

                <div class="max-h-72 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800">
                    <button
                        v-for="c in customers"
                        :key="c.id"
                        type="button"
                        @click="selectedCustomer = c; showCustomerSelectModal = false"
                        class="w-full py-3 px-3 rounded-xl flex items-center justify-between hover:bg-slate-50 dark:hover:bg-zinc-800/80 transition text-left cursor-pointer"
                        :class="selectedCustomer.id === c.id ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 font-semibold' : ''"
                    >
                        <div>
                            <div class="text-xs font-bold">{{ c.name }}</div>
                            <div class="text-[10px] text-zinc-500 font-mono">{{ c.phone }} • {{ c.customer_group }}</div>
                        </div>
                        <div class="text-right">
                            <span v-if="c.discount_rate > 0" class="text-xs font-mono font-bold text-emerald-600">
                                {{ c.discount_rate }}% Off
                            </span>
                            <span v-else class="text-[10px] text-zinc-400 font-mono">Standard</span>
                        </div>
                    </button>
                </div>

                <div class="flex justify-end pt-2">
                    <button
                        type="button"
                        @click="showCustomerSelectModal = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-xs font-medium cursor-pointer"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Order Discount Modal -->
        <Modal :show="showDiscountModal" @close="showDiscountModal = false">
            <div class="p-6 space-y-4">
                <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Apply Order Discount</h3>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Discount Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="tempDiscountType = 'percent'"
                            :class="[
                                'py-2 rounded-xl text-xs font-semibold border transition cursor-pointer',
                                tempDiscountType === 'percent' ? 'bg-emerald-50 dark:bg-emerald-600/20 text-emerald-800 dark:text-emerald-300 border-emerald-400' : 'bg-slate-50 dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800'
                            ]"
                        >
                            Percentage (%)
                        </button>
                        <button
                            type="button"
                            @click="tempDiscountType = 'fixed'"
                            :class="[
                                'py-2 rounded-xl text-xs font-semibold border transition cursor-pointer',
                                tempDiscountType === 'fixed' ? 'bg-emerald-50 dark:bg-emerald-600/20 text-emerald-800 dark:text-emerald-300 border-emerald-400' : 'bg-slate-50 dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800'
                            ]"
                        >
                            Fixed Amount ({{ terminal.currency_symbol }})
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        {{ tempDiscountType === 'percent' ? 'Discount Percentage (%)' : `Discount Amount (${terminal.currency_symbol})` }}
                    </label>
                    <input
                        v-model.number="tempDiscountValue"
                        type="number"
                        min="0"
                        :max="tempDiscountType === 'percent' ? 100 : calculatedSubtotal"
                        class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono text-zinc-900 dark:text-white font-bold focus:ring-2 focus:ring-emerald-500"
                    />
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button
                        type="button"
                        @click="showDiscountModal = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-xs font-medium cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="handleApplyOrderDiscount"
                        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow transition cursor-pointer"
                    >
                        Apply Discount
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>
