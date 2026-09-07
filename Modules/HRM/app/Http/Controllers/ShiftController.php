<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\HRM\Application\Actions\Shifts\CreateShiftAction;
use Modules\HRM\Application\Actions\Shifts\GetShiftAction;
use Modules\HRM\Application\Actions\Shifts\ListShiftsAction;
use Modules\HRM\Application\Actions\Shifts\ToggleShiftStatusAction;
use Modules\HRM\Application\Actions\Shifts\UpdateShiftAction;
use Modules\HRM\Http\Requests\IndexShiftRequest;
use Modules\HRM\Http\Requests\StoreShiftRequest;
use Modules\HRM\Http\Requests\UpdateShiftRequest;
use Modules\HRM\Http\Resources\ShiftResource;
use Modules\HRM\Models\Shift;

class ShiftController extends Controller
{
    public function index(
        IndexShiftRequest $request,
        ListShiftsAction $action,
    ): Response {
        $tenantId = app('currentTenant')->id;

        $shifts = $action->execute(
            tenantId: $tenantId,
            search: $request->search(),
            isActive: $request->isActive(),
            perPage: $request->perPage(),
        );

        $stats = [
            'total' => Shift::query()->where('tenant_id', $tenantId)->count(),
            'active' => Shift::query()->where('tenant_id', $tenantId)->where('is_active', true)->count(),
            'night' => Shift::query()->where('tenant_id', $tenantId)->where('is_overnight', true)->count(),
        ];

        return Inertia::render(
            'HRM/Shifts/Index',
            [
                'shifts' => ShiftResource::collection($shifts),
                'filters' => [
                    'search' => $request->search(),
                    'is_active' => $request->isActive(),
                    'per_page' => $request->perPage(),
                ],
                'stats' => $stats,
                'can' => [
                    'manage' => (bool) auth()->user()?->can('shifts.manage'),
                ],
            ]
        );
    }

    public function create(): Response
    {
        return Inertia::render(
            'HRM/Shifts/Create'
        );
    }

    public function store(
        StoreShiftRequest $request,
        CreateShiftAction $action,
    ): RedirectResponse {
        $tenantId = app('currentTenant')->id;

        $shift = $action->execute(
            $request->toData($tenantId)
        );

        return to_route(
            'admin.hrm.shifts.show',
            $shift->public_id
        )->with(
            'success',
            'Shift created successfully.'
        );
    }

    public function show(
        GetShiftAction $action,
        string $shift,
    ): Response {
        $tenantId = app('currentTenant')->id;

        $shiftModel = $action->execute(
            tenantId: $tenantId,
            publicId: $shift,
        );

        return Inertia::render(
            'HRM/Shifts/Show',
            [
                'shift' => new ShiftResource($shiftModel),
            ]
        );
    }

    public function edit(
        GetShiftAction $action,
        string $shift,
    ): Response {
        $tenantId = app('currentTenant')->id;

        $shiftModel = $action->execute(
            tenantId: $tenantId,
            publicId: $shift,
        );

        return Inertia::render(
            'HRM/Shifts/Edit',
            [
                'shift' => new ShiftResource($shiftModel),
            ]
        );
    }

    public function update(
        UpdateShiftRequest $request,
        UpdateShiftAction $action,
        string $shift,
    ): RedirectResponse {
        $tenantId = app('currentTenant')->id;

        $updatedShift = $action->execute(
            $request->toData(
                tenantId: $tenantId,
                publicId: $shift,
            )
        );

        return to_route(
            'admin.hrm.shifts.show',
            $updatedShift->public_id
        )->with(
            'success',
            'Shift updated successfully.'
        );
    }

    public function toggleStatus(
        ToggleShiftStatusAction $action,
        string $shift,
    ): RedirectResponse {
        $tenantId = app('currentTenant')->id;

        $updatedShift = $action->execute(
            tenantId: $tenantId,
            publicId: $shift,
        );

        return to_route(
            'admin.hrm.shifts.show',
            $updatedShift->public_id
        )->with(
            'success',
            $updatedShift->is_active
                ? 'Shift activated successfully.'
                : 'Shift deactivated successfully.'
        );
    }
}
