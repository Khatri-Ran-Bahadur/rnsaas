<?php

namespace Modules\POS\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\POS\Models\PosCashMovement;
use Modules\POS\Models\PosRegister;
use Modules\POS\Models\PosShift;

class PosShiftController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $activeShiftModel = PosShift::where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->with(['cashier', 'movements'])
            ->latest('opened_at')
            ->first();

        $activeShift = null;
        $movements = [];

        if ($activeShiftModel) {
            $expectedCash = (float) ($activeShiftModel->opening_float + $activeShiftModel->cash_sales + $activeShiftModel->cash_in - $activeShiftModel->cash_out);

            $activeShift = [
                'id' => $activeShiftModel->id,
                'shift_number' => $activeShiftModel->shift_number,
                'terminal_name' => $activeShiftModel->terminal_name,
                'cashier_id' => $activeShiftModel->user_id,
                'cashier_name' => $activeShiftModel->cashier?->name ?? 'Cashier',
                'cashier_role' => 'Manager / Lead Cashier',
                'opened_at' => $activeShiftModel->opened_at?->format('Y-m-d H:i:s') ?? now()->toDateTimeString(),
                'opening_float' => (float) $activeShiftModel->opening_float,
                'cash_sales' => (float) $activeShiftModel->cash_sales,
                'card_sales' => (float) $activeShiftModel->card_sales,
                'qr_sales' => (float) $activeShiftModel->qr_sales,
                'total_sales' => (float) $activeShiftModel->total_sales,
                'cash_refunds' => 0.00,
                'cash_in' => (float) $activeShiftModel->cash_in,
                'cash_out' => (float) $activeShiftModel->cash_out,
                'expected_cash' => $expectedCash,
                'status' => $activeShiftModel->status,
                'transaction_count' => (int) $activeShiftModel->transaction_count,
            ];

            $movements = $activeShiftModel->movements()
                ->latest()
                ->get()
                ->map(fn (PosCashMovement $m): array => [
                    'id' => $m->id,
                    'time' => $m->created_at?->format('Y-m-d H:i:s') ?? now()->toDateTimeString(),
                    'type' => $m->type,
                    'amount' => (float) $m->amount,
                    'reason' => $m->reason ?? '',
                    'authorized_by' => $m->authorized_by ?? 'Manager',
                ])
                ->all();
        }

        $allShifts = PosShift::where('tenant_id', $tenantId)
            ->with(['cashier', 'verifier'])
            ->latest('opened_at')
            ->get();

        $shifts = $allShifts->map(function (PosShift $shift): array {
            $expected = (float) ($shift->expected_cash ?? ($shift->opening_float + $shift->cash_sales + $shift->cash_in - $shift->cash_out));

            return [
                'id' => $shift->id,
                'shift_number' => $shift->shift_number,
                'terminal_name' => $shift->terminal_name,
                'cashier_name' => $shift->cashier?->name ?? 'Cashier',
                'opened_at' => $shift->opened_at?->format('Y-m-d H:i:s') ?? '',
                'closed_at' => $shift->closed_at?->format('Y-m-d H:i:s'),
                'opening_cash' => (float) $shift->opening_float,
                'opening_float' => (float) $shift->opening_float,
                'closing_cash' => $shift->actual_cash !== null ? (float) $shift->actual_cash : null,
                'expected_cash' => $expected,
                'variance' => (float) ($shift->variance ?? 0.00),
                'status' => $shift->status,
                'total_sales_count' => (int) $shift->transaction_count,
                'total_sales_amount' => (float) $shift->total_sales,
            ];
        })->all();

        $pastShifts = $allShifts->where('status', 'closed')->values()->map(function (PosShift $shift): array {
            return [
                'id' => $shift->id,
                'shift_number' => $shift->shift_number,
                'terminal_name' => $shift->terminal_name,
                'cashier_name' => $shift->cashier?->name ?? 'Cashier',
                'opened_at' => $shift->opened_at?->format('Y-m-d H:i:s') ?? '',
                'closed_at' => $shift->closed_at?->format('Y-m-d H:i:s') ?? '',
                'opening_float' => (float) $shift->opening_float,
                'total_sales' => (float) $shift->total_sales,
                'expected_cash' => (float) ($shift->expected_cash ?? ($shift->opening_float + $shift->cash_sales + $shift->cash_in - $shift->cash_out)),
                'actual_cash' => (float) ($shift->actual_cash ?? 0.00),
                'variance' => (float) ($shift->variance ?? 0.00),
                'status' => $shift->status,
                'verified_by' => $shift->verifier?->name ?? 'Manager',
            ];
        })->all();

        $stats = [
            'active_shifts_count' => PosShift::where('tenant_id', $tenantId)->where('status', 'open')->count(),
            'today_total_sales' => (float) PosShift::where('tenant_id', $tenantId)->whereDate('opened_at', today())->sum('total_sales'),
            'today_cash_collected' => (float) PosShift::where('tenant_id', $tenantId)->whereDate('opened_at', today())->sum('cash_sales'),
            'total_variance' => (float) PosShift::where('tenant_id', $tenantId)->sum('variance'),
        ];

        return Inertia::render('POS/Shifts/Index', [
            'shifts' => $shifts,
            'stats' => $stats,
            'activeShift' => $activeShift,
            'movements' => $movements,
            'pastShifts' => $pastShifts,
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'terminal_name' => ['nullable', 'string', 'max:100'],
            'opening_float' => ['required', 'numeric', 'min:0'],
        ]);

        $activeShift = PosShift::where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->first();

        if ($activeShift) {
            return redirect()->back()->with('error', 'There is already an active shift open. Please close it first.');
        }

        $register = null;
        if (! empty($validated['terminal_name'])) {
            $register = PosRegister::where('tenant_id', $tenantId)
                ->where('name', $validated['terminal_name'])
                ->first();
        }

        if (! $register) {
            $register = PosRegister::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first()
                ?? PosRegister::where('tenant_id', $tenantId)->first();
        }

        $terminalName = $register?->name ?? ($validated['terminal_name'] ?? 'Main POS Counter');

        $todayCount = PosShift::where('tenant_id', $tenantId)
            ->whereDate('opened_at', today())
            ->count();

        $shiftNumber = 'SH-'.now()->format('Ymd').'-'.sprintf('%02d', $todayCount + 1);
        $floatAmount = (float) $validated['opening_float'];

        $shift = PosShift::create([
            'tenant_id' => $tenantId,
            'register_id' => $register?->id,
            'user_id' => $request->user()?->id ?? 1,
            'shift_number' => $shiftNumber,
            'terminal_name' => $terminalName,
            'opened_at' => now(),
            'opening_float' => $floatAmount,
            'cash_sales' => 0.00,
            'card_sales' => 0.00,
            'qr_sales' => 0.00,
            'total_sales' => 0.00,
            'cash_in' => 0.00,
            'cash_out' => 0.00,
            'expected_cash' => $floatAmount,
            'transaction_count' => 0,
            'status' => 'open',
        ]);

        PosCashMovement::create([
            'tenant_id' => $tenantId,
            'shift_id' => $shift->id,
            'user_id' => $request->user()?->id ?? 1,
            'type' => 'opening_float',
            'amount' => $floatAmount,
            'reason' => 'Initial drawer float setup',
            'authorized_by' => $request->user()?->name ?? 'Manager',
        ]);

        return redirect()->back()->with('success', "Shift {$shiftNumber} opened successfully.");
    }

    public function recordMovement(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:cash_in,cash_out'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $activeShift = PosShift::where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->firstOrFail();

        $amount = (float) $validated['amount'];

        if ($validated['type'] === 'cash_in') {
            $activeShift->cash_in += $amount;
        } else {
            $activeShift->cash_out += $amount;
        }

        $activeShift->expected_cash = (float) ($activeShift->opening_float + $activeShift->cash_sales + $activeShift->cash_in - $activeShift->cash_out);
        $activeShift->save();

        PosCashMovement::create([
            'tenant_id' => $tenantId,
            'shift_id' => $activeShift->id,
            'user_id' => $request->user()?->id ?? 1,
            'type' => $validated['type'],
            'amount' => $validated['type'] === 'cash_out' ? -$amount : $amount,
            'reason' => $validated['reason'],
            'authorized_by' => $request->user()?->name ?? 'Manager',
        ]);

        return redirect()->back()->with('success', 'Cash movement recorded in drawer log.');
    }

    public function close(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $activeShift = PosShift::where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->firstOrFail();

        $expectedCash = (float) ($activeShift->opening_float + $activeShift->cash_sales + $activeShift->cash_in - $activeShift->cash_out);
        $actualCash = (float) $validated['actual_cash'];
        $variance = $actualCash - $expectedCash;

        $activeShift->update([
            'status' => 'closed',
            'closed_at' => now(),
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'variance' => $variance,
            'notes' => $validated['notes'] ?? null,
            'verified_by' => $request->user()?->id ?? 1,
        ]);

        return redirect()->back()->with('success', 'Shift closed and Z-Report generated.');
    }
}
