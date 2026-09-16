<?php

namespace Modules\MRP\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\MRP\Models\MrpWorkOrder;

class MrpCalendarController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $events = MrpWorkOrder::where('tenant_id', $tenantId)
            ->with('workCenter')
            ->get()
            ->map(function ($wo) {
                $color = match ($wo->status) {
                    'completed' => '#10b981',
                    'in_progress' => '#3b82f6',
                    'paused' => '#f59e0b',
                    default => '#8b5cf6',
                };

                return [
                    'id' => $wo->id,
                    'title' => "{$wo->wo_number} ({$wo->product_name})",
                    'work_center' => $wo->workCenter?->name ?? 'Main Production Line',
                    'start' => $wo->planned_start_date?->format('Y-m-d H:i') ?? now()->format('Y-m-d 08:00'),
                    'end' => $wo->planned_end_date?->format('Y-m-d H:i') ?? now()->addDay()->format('Y-m-d 17:00'),
                    'status' => ucfirst(str_replace('_', ' ', $wo->status)),
                    'priority' => ucfirst($wo->priority ?? 'normal'),
                    'quantity' => (float) $wo->planned_qty,
                    'color' => $color,
                ];
            })->toArray();

        return Inertia::render('MRP/Calendar/Index', [
            'events' => $events,
        ]);
    }
}
