<?php

namespace Modules\POS\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\POS\Models\PosCashMovement;
use Modules\POS\Models\PosOrder;
use Modules\POS\Models\PosOrderItem;
use Modules\POS\Models\PosShift;

class PosOrderController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $query = PosOrder::where('tenant_id', $tenantId)
            ->with(['items', 'table', 'shift', 'user']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search): void {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', 'like', '%'.$request->query('payment_method').'%');
        }

        $dateRange = $request->query('date_range', 'today');
        if ($dateRange === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($dateRange === 'yesterday') {
            $query->whereDate('created_at', today()->subDay());
        } elseif ($dateRange === 'this_week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($dateRange === 'this_month') {
            $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
        }

        $allOrders = PosOrder::where('tenant_id', $tenantId)->get();

        $metrics = [
            'total_orders' => $allOrders->count(),
            'completed_orders' => $allOrders->where('status', 'completed')->count(),
            'refunded_orders' => $allOrders->where('status', 'refunded')->count(),
            'voided_orders' => $allOrders->where('status', 'voided')->count(),
            'gross_sales' => (float) $allOrders->where('status', 'completed')->sum('grand_total'),
            'net_sales' => (float) $allOrders->where('status', 'completed')->sum('subtotal'),
            'total_tax' => (float) $allOrders->where('status', 'completed')->sum('tax_total'),
            'total_discounts' => (float) $allOrders->where('status', 'completed')->sum('discount_total'),
        ];

        $orders = $query->latest()->get()->map(function (PosOrder $order): array {
            $orderItems = $order->items->map(fn (PosOrderItem $item): array => [
                'id' => $item->id,
                'name' => $item->item_name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount_amount,
                'tax' => (float) $item->tax_amount,
                'total' => (float) $item->total_price,
                'modifiers' => $item->modifiers ?? [],
                'notes' => $item->notes ?? '',
            ])->all();

            $dateStr = $order->created_at?->format('Y-m-d H:i:s') ?? now()->toDateTimeString();

            return [
                'id' => $order->id,
                'invoice_no' => $order->order_number,
                'invoice_number' => $order->order_number,
                'created_at' => $dateStr,
                'date' => $dateStr,
                'customer_name' => $order->customer_name ?? 'Walk-in Customer',
                'terminal_name' => $order->shift?->terminal_name ?? 'Counter 01',
                'cashier_name' => $order->cashier_name ?? $order->user?->name ?? 'Ran Bahadur Khatri',
                'order_type' => $order->order_type ?? 'dine_in',
                'table_name' => $order->table?->name,
                'subtotal' => (float) $order->subtotal,
                'discount_amount' => (float) $order->discount_total,
                'discount_total' => (float) $order->discount_total,
                'tax_amount' => (float) $order->tax_total,
                'tax_total' => (float) $order->tax_total,
                'grand_total' => (float) $order->grand_total,
                'paid_amount' => (float) $order->paid_amount,
                'change_amount' => (float) $order->change_amount,
                'payment_method' => $order->payment_method ?? 'Cash',
                'status' => $order->status,
                'items_count' => $order->items->count(),
                'items' => $orderItems,
                'payments' => [
                    [
                        'method' => $order->payment_method ?? 'Cash',
                        'amount' => (float) $order->paid_amount,
                        'ref' => $order->order_number,
                    ],
                ],
            ];
        })->all();

        return Inertia::render('POS/Orders/History', [
            'filters' => [
                'search' => $request->query('search', ''),
                'status' => $request->query('status', ''),
                'payment_method' => $request->query('payment_method', ''),
                'date_range' => $dateRange,
            ],
            'orders' => $orders,
            'metrics' => $metrics,
        ]);
    }

    public function show(Request $request, int $id): Response
    {
        $tenantId = $this->getTenantId($request);

        $order = PosOrder::where('tenant_id', $tenantId)
            ->with(['items', 'table', 'shift', 'user'])
            ->findOrFail($id);

        return Inertia::render('POS/Orders/Show', [
            'orderId' => $id,
            'order' => $order,
        ]);
    }

    public function refund(Request $request, int $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $order = PosOrder::where('tenant_id', $tenantId)->findOrFail($id);
        $order->status = 'refunded';
        $order->notes = ($order->notes ? $order->notes."\n" : '').'Refunded on '.now()->toDateTimeString().($request->input('reason') ? ': '.$request->input('reason') : '');
        $order->save();

        if ($order->shift_id) {
            $shift = PosShift::find($order->shift_id);
            if ($shift && $shift->status === 'open') {
                if (str_contains(strtolower($order->payment_method ?? ''), 'cash')) {
                    PosCashMovement::create([
                        'tenant_id' => $tenantId,
                        'shift_id' => $shift->id,
                        'user_id' => $request->user()?->id ?? 1,
                        'type' => 'cash_out',
                        'amount' => -(float) $order->grand_total,
                        'reason' => 'Refund for Order '.$order->order_number,
                        'authorized_by' => $request->user()?->name ?? 'Manager',
                    ]);

                    $shift->cash_out += (float) $order->grand_total;
                    $shift->expected_cash = (float) ($shift->opening_float + $shift->cash_sales + $shift->cash_in - $shift->cash_out);
                    $shift->save();
                }
            }
        }

        return redirect()->back()->with('success', 'Refund processed and recorded in sales history.');
    }
}
