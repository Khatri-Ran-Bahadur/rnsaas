<?php

namespace Modules\POS\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\POS\Models\PosKitchenStation;
use Modules\POS\Models\PosKitchenTicket;

class PosKitchenController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $dbStations = PosKitchenStation::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $allPendingCount = PosKitchenTicket::where('tenant_id', $tenantId)
            ->whereIn('status', ['new', 'preparing', 'ready'])
            ->count();

        $stations = collect([
            [
                'id' => 'all',
                'name' => 'All Stations',
                'icon' => 'LayoutGrid',
                'pending_count' => $allPendingCount,
            ],
        ]);

        if ($dbStations->isNotEmpty()) {
            foreach ($dbStations as $station) {
                $count = PosKitchenTicket::where('tenant_id', $tenantId)
                    ->where('station', $station->code)
                    ->whereIn('status', ['new', 'preparing', 'ready'])
                    ->count();

                $stations->push([
                    'id' => $station->code,
                    'name' => $station->name,
                    'icon' => $station->icon ?? 'Utensils',
                    'pending_count' => $count,
                ]);
            }
        } else {
            $distinctStations = PosKitchenTicket::where('tenant_id', $tenantId)
                ->whereNotNull('station')
                ->distinct()
                ->pluck('station');

            foreach ($distinctStations as $st) {
                $count = PosKitchenTicket::where('tenant_id', $tenantId)
                    ->where('station', $st)
                    ->whereIn('status', ['new', 'preparing', 'ready'])
                    ->count();

                $stations->push([
                    'id' => $st,
                    'name' => ucwords(str_replace('_', ' ', $st)),
                    'icon' => 'Utensils',
                    'pending_count' => $count,
                ]);
            }
        }

        $tickets = PosKitchenTicket::where('tenant_id', $tenantId)
            ->with('items')
            ->whereIn('status', ['new', 'preparing', 'ready'])
            ->latest()
            ->get()
            ->map(function (PosKitchenTicket $ticket): array {
                $elapsedSeconds = $ticket->created_at ? max(0, $ticket->created_at->diffInSeconds(now())) : 0;
                $elapsedMinutes = $ticket->created_at ? max(1, (int) round($elapsedSeconds / 60)) : 1;

                return [
                    'id' => (string) $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'order_number' => $ticket->order_ref ?? $ticket->ticket_number,
                    'order_id' => $ticket->order_ref ?? $ticket->ticket_number,
                    'order_type' => $ticket->order_type,
                    'destination' => $ticket->destination ?? 'Table',
                    'table_name' => $ticket->destination ?? 'Table',
                    'station' => $ticket->station,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'created_at' => $ticket->created_at?->format('Y-m-d H:i:s') ?? now()->toDateTimeString(),
                    'elapsed_seconds' => $elapsedSeconds,
                    'elapsed_minutes' => $elapsedMinutes,
                    'server_name' => $ticket->server_name ?? 'Staff',
                    'notes' => $ticket->notes ?? '',
                    'items' => $ticket->items->map(fn ($item): array => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => (int) $item->quantity,
                        'modifiers' => $item->modifiers ?? [],
                        'notes' => $item->notes ?? '',
                        'done' => (bool) $item->is_done,
                        'is_done' => (bool) $item->is_done,
                    ])->all(),
                ];
            })
            ->all();

        return Inertia::render('POS/Kitchen/Kds', [
            'stations' => $stations->values()->toArray(),
            'tickets' => $tickets,
            'currentStation' => $request->query('station', 'all'),
        ]);
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'id' => ['required'],
            'status' => ['required', 'string', 'in:new,preparing,ready,served,cancelled'],
        ]);

        $ticket = PosKitchenTicket::where('tenant_id', $tenantId)
            ->where(function ($query) use ($validated): void {
                $query->where('id', $validated['id'])
                    ->orWhere('ticket_number', $validated['id'])
                    ->orWhere('public_id', $validated['id']);
            })
            ->firstOrFail();

        $ticket->status = $validated['status'];

        if ($validated['status'] === 'preparing' && ! $ticket->prepared_at) {
            $ticket->prepared_at = now();
        } elseif ($validated['status'] === 'served' && ! $ticket->served_at) {
            $ticket->served_at = now();
        }

        $ticket->save();

        return redirect()->back()->with('success', 'Ticket status updated.');
    }
}
