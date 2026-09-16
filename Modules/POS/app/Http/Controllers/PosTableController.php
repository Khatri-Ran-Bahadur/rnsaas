<?php

namespace Modules\POS\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\POS\Models\PosTable;

class PosTableController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $sectionFloorMap = [
            'Main Dining' => 1,
            'VIP Lounge' => 2,
            'Outdoor Terrace' => 3,
        ];

        $floors = [
            [
                'id' => 1,
                'name' => 'Ground Floor - Main Dining',
                'tables_count' => PosTable::where('tenant_id', $tenantId)->where('section', 'Main Dining')->count(),
                'total_tables' => PosTable::where('tenant_id', $tenantId)->where('section', 'Main Dining')->count(),
                'active_tables' => PosTable::where('tenant_id', $tenantId)->where('section', 'Main Dining')->where('status', 'occupied')->count(),
            ],
            [
                'id' => 2,
                'name' => '1st Floor - VIP Lounge & Rooms',
                'tables_count' => PosTable::where('tenant_id', $tenantId)->where('section', 'VIP Lounge')->count(),
                'total_tables' => PosTable::where('tenant_id', $tenantId)->where('section', 'VIP Lounge')->count(),
                'active_tables' => PosTable::where('tenant_id', $tenantId)->where('section', 'VIP Lounge')->where('status', 'occupied')->count(),
            ],
            [
                'id' => 3,
                'name' => 'Outdoor Garden Terrace',
                'tables_count' => PosTable::where('tenant_id', $tenantId)->where('section', 'Outdoor Terrace')->count(),
                'total_tables' => PosTable::where('tenant_id', $tenantId)->where('section', 'Outdoor Terrace')->count(),
                'active_tables' => PosTable::where('tenant_id', $tenantId)->where('section', 'Outdoor Terrace')->where('status', 'occupied')->count(),
            ],
        ];

        $tables = PosTable::where('tenant_id', $tenantId)
            ->orderBy('id')
            ->get()
            ->map(function (PosTable $table) use ($sectionFloorMap): array {
                $floorId = $sectionFloorMap[$table->section] ?? 1;

                return [
                    'id' => $table->id,
                    'floor_id' => $floorId,
                    'name' => $table->name,
                    'capacity' => (int) $table->capacity,
                    'status' => $table->status,
                    'guest_count' => $table->status === 'occupied' ? max(1, $table->capacity - 1) : 0,
                    'server_name' => $table->assigned_server,
                    'order_id' => $table->current_order_ref,
                    'current_order_id' => $table->current_order_ref,
                    'current_order_total' => (float) $table->current_order_total,
                    'subtotal' => (float) $table->current_order_total,
                    'occupied_minutes' => (int) $table->occupied_minutes,
                    'occupied_since' => $table->occupied_minutes ? now()->subMinutes($table->occupied_minutes)->format('H:i') : null,
                    'elapsed_minutes' => (int) $table->occupied_minutes,
                    'items_count' => $table->current_order_ref ? 4 : 0,
                    'kot_status' => $table->status === 'occupied' ? 'preparing' : null,
                ];
            })
            ->all();

        $waiters = [
            ['id' => 1, 'name' => 'Farhan (Waiter)'],
            ['id' => 2, 'name' => 'Siti (Waitress)'],
            ['id' => 3, 'name' => 'Ahmad (Captain)'],
            ['id' => 4, 'name' => 'Kamal (Runner)'],
        ];

        return Inertia::render('POS/Restaurant/FloorPlan', [
            'floors' => $floors,
            'tables' => $tables,
            'currentFloorId' => (int) $request->query('floor', 1),
            'waiters' => $waiters,
        ]);
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'table_id' => ['required'],
            'status' => ['required', 'string', 'in:available,occupied,reserved,cleaning'],
        ]);

        $table = PosTable::where('tenant_id', $tenantId)
            ->where(function ($query) use ($validated): void {
                $query->where('id', $validated['table_id'])
                    ->orWhere('public_id', $validated['table_id']);
            })
            ->firstOrFail();

        $table->status = $validated['status'];

        if ($validated['status'] === 'available') {
            $table->current_order_ref = null;
            $table->current_order_total = 0;
            $table->occupied_minutes = 0;
            $table->assigned_server = null;
        }

        $table->save();

        return redirect()->back()->with('success', 'Table status updated.');
    }

    public function move(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'from_table_id' => ['required'],
            'to_table_id' => ['required'],
        ]);

        $fromTable = PosTable::where('tenant_id', $tenantId)->where('id', $validated['from_table_id'])->firstOrFail();
        $toTable = PosTable::where('tenant_id', $tenantId)->where('id', $validated['to_table_id'])->firstOrFail();

        $toTable->status = 'occupied';
        $toTable->current_order_ref = $fromTable->current_order_ref;
        $toTable->current_order_total = $fromTable->current_order_total;
        $toTable->assigned_server = $fromTable->assigned_server;
        $toTable->occupied_minutes = $fromTable->occupied_minutes;
        $toTable->save();

        $fromTable->status = 'available';
        $fromTable->current_order_ref = null;
        $fromTable->current_order_total = 0;
        $fromTable->assigned_server = null;
        $fromTable->occupied_minutes = 0;
        $fromTable->save();

        return redirect()->back()->with('success', 'Order transferred to new table.');
    }

    public function merge(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'primary_table_id' => ['required'],
            'secondary_table_ids' => ['required', 'array'],
        ]);

        $primaryTable = PosTable::where('tenant_id', $tenantId)->where('id', $validated['primary_table_id'])->firstOrFail();
        $secondaryTables = PosTable::where('tenant_id', $tenantId)->whereIn('id', $validated['secondary_table_ids'])->get();

        $additionalTotal = $secondaryTables->sum('current_order_total');
        $primaryTable->current_order_total += $additionalTotal;
        $primaryTable->save();

        foreach ($secondaryTables as $secondaryTable) {
            $secondaryTable->status = 'occupied';
            $secondaryTable->current_order_ref = $primaryTable->current_order_ref;
            $secondaryTable->save();
        }

        return redirect()->back()->with('success', 'Tables merged successfully.');
    }
}
