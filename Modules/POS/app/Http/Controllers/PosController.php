<?php

namespace Modules\POS\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Models\Customer;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;
use Modules\POS\Models\PosKitchenTicket;
use Modules\POS\Models\PosKitchenTicketItem;
use Modules\POS\Models\PosOrder;
use Modules\POS\Models\PosOrderItem;
use Modules\POS\Models\PosRegister;
use Modules\POS\Models\PosShift;
use Modules\POS\Models\PosTable;
use Modules\Tax\Models\TaxRate;
use Modules\Tax\Models\TaxSetting;
use Modules\Tenancy\Models\Tenant;

class PosController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $register = PosRegister::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first()
            ?? PosRegister::where('tenant_id', $tenantId)->first();

        $activeShift = PosShift::where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->with('cashier')
            ->latest('opened_at')
            ->first();

        if ($activeShift) {
            $expectedCash = (float) ($activeShift->opening_float + $activeShift->cash_sales + $activeShift->cash_in - $activeShift->cash_out);
            $currentShift = [
                'id' => $activeShift->id,
                'shift_number' => $activeShift->shift_number,
                'cashier_id' => $activeShift->user_id,
                'cashier_name' => $activeShift->cashier?->name ?? ($request->user()?->name ?? 'Cashier'),
                'cashier_role' => 'Manager',
                'opened_at' => $activeShift->opened_at?->format('Y-m-d H:i:s') ?? now()->toDateTimeString(),
                'opening_cash' => (float) $activeShift->opening_float,
                'status' => 'open',
                'total_sales_count' => (int) $activeShift->transaction_count,
                'total_sales_amount' => (float) $activeShift->total_sales,
                'cash_sales_amount' => (float) $activeShift->cash_sales,
                'cash_in' => (float) $activeShift->cash_in,
                'cash_out' => (float) $activeShift->cash_out,
                'expected_cash' => $expectedCash,
            ];
        } else {
            $currentShift = [
                'id' => 0,
                'shift_number' => 'None',
                'cashier_id' => $request->user()?->id ?? 1,
                'cashier_name' => $request->user()?->name ?? 'Cashier',
                'cashier_role' => 'Manager',
                'opened_at' => null,
                'opening_cash' => 0.00,
                'status' => 'closed',
                'total_sales_count' => 0,
                'total_sales_amount' => 0.00,
                'cash_sales_amount' => 0.00,
                'cash_in' => 0.00,
                'cash_out' => 0.00,
                'expected_cash' => 0.00,
            ];
        }

        $rawCategories = InventoryCategory::where('tenant_id', $tenantId)->withCount('items')->get();
        $categoriesList = collect([
            [
                'id' => 'all',
                'name' => 'All Items',
                'slug' => 'all',
                'icon' => 'Grid',
                'count' => InventoryItem::where('tenant_id', $tenantId)->where('is_pos_available', true)->count(),
            ],
        ]);

        foreach ($rawCategories as $rc) {
            $categoriesList->push([
                'id' => $rc->id,
                'name' => $rc->name,
                'slug' => $rc->slug,
                'icon' => str_contains(strtolower($rc->name), 'beverage') ? 'Coffee' : (str_contains(strtolower($rc->name), 'burger') || str_contains(strtolower($rc->name), 'food') ? 'Flame' : 'Utensils'),
                'count' => (int) $rc->items_count,
            ]);
        }

        $rawProducts = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_pos_available', true)
            ->with('category:id,name')
            ->get();

        $productsList = $rawProducts->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
                'category_id' => $item->category_id,
                'category_name' => $item->category?->name ?? 'General',
                'price' => (float) $item->selling_price,
                'wholesale_price' => (float) ($item->selling_price * 0.85),
                'cost_price' => (float) $item->cost_price,
                'current_stock' => (float) $item->on_hand_stock,
                'is_favorite' => (bool) $item->is_favorite,
                'track_stock' => (bool) $item->track_stock,
                'has_variants' => false,
                'has_modifiers' => ! empty($item->modifier_groups),
                'image' => null,
                'modifier_groups' => $item->modifier_groups ?? [],
            ];
        })->toArray();

        $rawCustomers = Customer::where('tenant_id', $tenantId)->get();
        if ($rawCustomers->isEmpty()) {
            $customersList = [
                [
                    'id' => 1,
                    'name' => 'Walk-in Customer',
                    'phone' => '-',
                    'email' => '-',
                    'customer_group' => 'Standard Retail',
                    'price_tier' => 'retail',
                    'discount_rate' => 0,
                    'credit_limit' => 0.00,
                    'outstanding_balance' => 0.00,
                    'tax_number' => null,
                    'is_default' => true,
                ],
            ];
        } else {
            $customersList = $rawCustomers->map(function ($c, $idx) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone ?? '-',
                    'email' => $c->email ?? '-',
                    'customer_group' => 'Standard Retail',
                    'price_tier' => 'retail',
                    'discount_rate' => 0,
                    'credit_limit' => (float) ($c->credit_limit ?? 0),
                    'outstanding_balance' => 0.00,
                    'tax_number' => $c->tax_number,
                    'is_default' => $idx === 0,
                ];
            })->toArray();
        }

        $tenant = $request->user()?->tenants()->where('tenants.id', $tenantId)->first()
            ?? Tenant::find($tenantId);

        $taxSetting = TaxSetting::where('tenant_id', $tenantId)->first();
        $taxRates = TaxRate::where('tenant_id', $tenantId)
            ->where('timeline_status', 'active')
            ->get(['code', 'name', 'rate'])
            ->map(fn ($r) => [
                'code' => $r->code,
                'name' => $r->name,
                'rate' => (float) $r->rate,
            ])
            ->toArray();

        $taxProfile = [
            'name' => ($taxSetting?->tax_regime ? strtoupper($taxSetting->tax_regime) : 'Tax').' Profile',
            'inclusive' => ($taxSetting?->default_pricing_mode ?? 'exclusive') === 'inclusive',
            'rates' => ! empty($taxRates) ? $taxRates : [
                ['code' => 'SR-8', 'name' => 'Standard Rate (8%)', 'rate' => 8.00],
            ],
        ];

        return Inertia::render('POS/Register', [
            'terminal' => [
                'id' => $register?->id ?? 1,
                'name' => $register?->name ?? 'Main POS Terminal',
                'branch_id' => 1,
                'branch_name' => $register?->location ?? 'Main Outlet',
                'company_name' => $tenant?->name ?? config('app.name', 'SathiSaaS'),
                'device_ip' => $register?->receipt_printer_ip ?? '192.168.1.180',
                'currency' => strtoupper((string) ($tenant?->currency ?? 'USD')),
                'currency_symbol' => match (strtoupper((string) ($tenant?->currency ?? 'USD'))) {
                    'USD' => '$',
                    'EUR' => '€',
                    'GBP' => '£',
                    'INR' => '₹',
                    'NPR' => 'रू',
                    'MYR' => 'RM',
                    default => strtoupper((string) ($tenant?->currency ?? 'USD')),
                },
                'tax_inclusive' => ($taxSetting?->default_pricing_mode ?? 'exclusive') === 'inclusive',
                'default_tax_rate' => (float) ($taxSetting?->salesTaxRate?->rate ?? 8.00),
                'allow_custom_price' => true,
                'allow_discounts' => true,
                'max_discount_cashier' => 10,
                'max_discount_manager' => 30,
            ],
            'currentShift' => $currentShift,
            'categories' => $categoriesList->toArray(),
            'products' => $productsList,
            'customers' => $customersList,
            'paymentMethods' => [
                ['id' => 'cash', 'name' => 'Cash', 'type' => 'cash', 'icon' => 'Banknote', 'enabled' => true, 'requires_ref' => false],
                ['id' => 'card', 'name' => 'Credit / Debit Card', 'type' => 'card', 'icon' => 'CreditCard', 'enabled' => true, 'requires_ref' => true],
                ['id' => 'qr', 'name' => match ($tenant?->country_code) {
                    'MY' => 'DuitNow / QR Pay',
                    'NP' => 'Fonepay / QR Pay',
                    'IN' => 'UPI / QR Pay',
                    'SG' => 'PayNow / QR Pay',
                    'GB' => 'Open Banking / QR Pay',
                    default => 'Instant QR Code Pay',
                }, 'type' => 'qr', 'icon' => 'QrCode', 'enabled' => true, 'requires_ref' => true],
                ['id' => 'wallet', 'name' => match ($tenant?->country_code) {
                    'MY' => 'E-Wallet (Touch n Go / GrabPay)',
                    'NP' => 'Digital Wallet (eSewa / Khalti)',
                    'IN' => 'Digital Wallet (Paytm / PhonePe)',
                    'US' => 'Digital Wallet (Apple Pay / Google Pay)',
                    default => 'Digital Wallet / Mobile Pay',
                }, 'type' => 'wallet', 'icon' => 'Smartphone', 'enabled' => true, 'requires_ref' => true],
                ['id' => 'credit', 'name' => 'Customer Credit (On-Account)', 'type' => 'credit', 'icon' => 'UserCheck', 'enabled' => true, 'requires_ref' => false],
                ['id' => 'bank', 'name' => 'Bank Transfer', 'type' => 'bank', 'icon' => 'Building2', 'enabled' => true, 'requires_ref' => true],
            ],
            'heldOrders' => PosOrder::where('tenant_id', $tenantId)
                ->where('status', 'held')
                ->with(['items', 'table'])
                ->latest()
                ->get()
                ->map(fn (PosOrder $order): array => [
                    'id' => $order->order_number,
                    'held_at' => $order->created_at?->format('Y-m-d H:i:s') ?? now()->toDateTimeString(),
                    'customer_name' => $order->customer_name ?? 'Walk-in Customer',
                    'table_number' => $order->table?->name ?? ($order->order_type === 'takeaway' ? 'Takeaway Pickup' : 'Counter'),
                    'order_type' => $order->order_type ?? 'dine_in',
                    'items_count' => $order->items->count(),
                    'total_amount' => (float) $order->grand_total,
                    'cart_items' => $order->items->map(fn (PosOrderItem $item): array => [
                        'id' => $item->id,
                        'name' => $item->item_name,
                        'price' => (float) $item->unit_price,
                        'quantity' => (float) $item->quantity,
                        'discount_amount' => (float) $item->discount_amount,
                        'modifiers' => $item->modifiers ?? [],
                        'notes' => $item->notes ?? '',
                    ])->all(),
                ])
                ->all(),
            'taxProfile' => $taxProfile,
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'order_type' => ['nullable', 'string'],
            'table_name' => ['nullable', 'string'],
            'subtotal' => ['required', 'numeric'],
            'tax_total' => ['nullable', 'numeric'],
            'discount_total' => ['nullable', 'numeric'],
            'grand_total' => ['required', 'numeric'],
            'paid_amount' => ['nullable', 'numeric'],
            'change_amount' => ['nullable', 'numeric'],
            'payment_method' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.name' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric'],
            'items.*.unit_price' => ['required', 'numeric'],
            'items.*.total' => ['required', 'numeric'],
            'items.*.modifiers' => ['nullable', 'array'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $activeShift = PosShift::where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $table = null;
        if (! empty($validated['table_name'])) {
            $table = PosTable::where('tenant_id', $tenantId)
                ->where('name', $validated['table_name'])
                ->first();
        }

        $count = PosOrder::where('tenant_id', $tenantId)->whereDate('created_at', today())->count();
        $orderNumber = 'ORD-'.now()->format('Ymd').'-'.sprintf('%04d', $count + 1);

        $order = PosOrder::create([
            'tenant_id' => $tenantId,
            'shift_id' => $activeShift?->id,
            'register_id' => $activeShift?->register_id,
            'user_id' => $request->user()?->id ?? 1,
            'table_id' => $table?->id,
            'order_number' => $orderNumber,
            'order_type' => $validated['order_type'] ?? 'dine_in',
            'customer_name' => $validated['customer_name'] ?? 'Walk-in Customer',
            'customer_phone' => $validated['customer_phone'] ?? null,
            'subtotal' => $validated['subtotal'],
            'tax_total' => $validated['tax_total'] ?? 0,
            'discount_total' => $validated['discount_total'] ?? 0,
            'grand_total' => $validated['grand_total'],
            'paid_amount' => $validated['paid_amount'] ?? $validated['grand_total'],
            'change_amount' => $validated['change_amount'] ?? 0,
            'payment_method' => $validated['payment_method'] ?? 'Cash',
            'status' => 'completed',
            'cashier_name' => $request->user()?->name ?? 'Cashier',
        ]);

        foreach ($validated['items'] as $itemData) {
            $prodId = $itemData['product_id'] ?? null;
            $sku = $itemData['sku'] ?? $itemData['item_code'] ?? null;

            if (! $prodId && ! empty($itemData['name'])) {
                $invItem = InventoryItem::where('tenant_id', $tenantId)->where('name', $itemData['name'])->first();
                $prodId = $invItem?->id;
                $sku ??= $invItem?->sku;
            }

            PosOrderItem::create([
                'tenant_id' => $tenantId,
                'order_id' => $order->id,
                'product_id' => $prodId,
                'item_name' => $itemData['name'],
                'item_code' => $sku,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'discount_amount' => $itemData['discount'] ?? 0,
                'tax_amount' => $itemData['tax'] ?? 0,
                'total_price' => $itemData['total'],
                'modifiers' => $itemData['modifiers'] ?? null,
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        if ($activeShift) {
            $grandTotal = (float) $validated['grand_total'];
            $pm = strtolower($validated['payment_method'] ?? 'cash');

            if (str_contains($pm, 'cash')) {
                $activeShift->cash_sales += $grandTotal;
            } elseif (str_contains($pm, 'card') || str_contains($pm, 'visa') || str_contains($pm, 'master')) {
                $activeShift->card_sales += $grandTotal;
            } else {
                $activeShift->qr_sales += $grandTotal;
            }

            $activeShift->total_sales += $grandTotal;
            $activeShift->transaction_count += 1;
            $activeShift->expected_cash = (float) ($activeShift->opening_float + $activeShift->cash_sales + $activeShift->cash_in - $activeShift->cash_out);
            $activeShift->save();
        }

        if (in_array($validated['order_type'] ?? 'dine_in', ['dine_in', 'takeaway'])) {
            $ticketCount = PosKitchenTicket::where('tenant_id', $tenantId)->whereDate('created_at', today())->count();
            $ticketNumber = 'KOT-'.now()->format('Ymd').'-'.sprintf('%03d', $ticketCount + 1);

            $ticket = PosKitchenTicket::create([
                'tenant_id' => $tenantId,
                'order_id' => $order->id,
                'ticket_number' => $ticketNumber,
                'order_ref' => $orderNumber,
                'order_type' => $validated['order_type'] ?? 'dine_in',
                'destination' => $validated['table_name'] ?? ($validated['order_type'] === 'takeaway' ? 'Takeaway Counter' : 'Counter'),
                'station' => 'kitchen',
                'priority' => 'normal',
                'status' => 'new',
                'server_name' => $request->user()?->name ?? 'Cashier',
                'notes' => $request->input('notes'),
            ]);

            foreach ($validated['items'] as $itemData) {
                PosKitchenTicketItem::create([
                    'ticket_id' => $ticket->id,
                    'name' => $itemData['name'],
                    'quantity' => $itemData['quantity'],
                    'modifiers' => $itemData['modifiers'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                    'is_done' => false,
                ]);
            }
        }

        if ($table) {
            $table->status = 'occupied';
            $table->current_order_ref = $orderNumber;
            $table->current_order_total = $validated['grand_total'];
            $table->occupied_minutes = 1;
            $table->assigned_server = $request->user()?->name ?? 'Cashier';
            $table->save();
        }

        return redirect()->back()->with('success', "Order {$orderNumber} completed successfully.");
    }

    public function hold(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'order_type' => ['nullable', 'string'],
            'table_name' => ['nullable', 'string'],
            'total_amount' => ['required', 'numeric'],
            'cart_items' => ['required', 'array'],
        ]);

        $count = PosOrder::where('tenant_id', $tenantId)->whereDate('created_at', today())->count();
        $holdNumber = 'HOLD-'.now()->format('Ymd').'-'.sprintf('%03d', $count + 1);

        $order = PosOrder::create([
            'tenant_id' => $tenantId,
            'order_number' => $holdNumber,
            'order_type' => $validated['order_type'] ?? 'dine_in',
            'customer_name' => $validated['customer_name'] ?? 'Walk-in Customer',
            'subtotal' => $validated['total_amount'],
            'grand_total' => $validated['total_amount'],
            'status' => 'held',
            'cashier_name' => $request->user()?->name ?? 'Cashier',
        ]);

        foreach ($validated['cart_items'] as $itemData) {
            PosOrderItem::create([
                'tenant_id' => $tenantId,
                'order_id' => $order->id,
                'item_name' => $itemData['name'],
                'quantity' => $itemData['quantity'] ?? 1,
                'unit_price' => $itemData['price'] ?? 0,
                'total_price' => ($itemData['price'] ?? 0) * ($itemData['quantity'] ?? 1),
                'modifiers' => $itemData['modifiers'] ?? null,
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        return redirect()->back()->with('success', "Order held successfully as {$holdNumber}.");
    }

    public function resume(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $orderNumber = $request->input('order_number') ?? $request->input('id');

        if ($orderNumber) {
            $order = PosOrder::where('tenant_id', $tenantId)
                ->where('order_number', $orderNumber)
                ->where('status', 'held')
                ->first();

            if ($order) {
                $order->status = 'voided';
                $order->save();
            }
        }

        return redirect()->back()->with('success', 'Order resumed to active cart.');
    }

    public function void(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $orderNumber = $request->input('order_number') ?? $request->input('id');

        if ($orderNumber) {
            $order = PosOrder::where('tenant_id', $tenantId)
                ->where('order_number', $orderNumber)
                ->first();

            if ($order) {
                $order->status = 'voided';
                $order->notes = ($order->notes ? $order->notes."\n" : '').'Voided by manager on '.now()->toDateTimeString();
                $order->save();
            }
        }

        return redirect()->back()->with('success', 'Transaction voided with manager authorization.');
    }
}
