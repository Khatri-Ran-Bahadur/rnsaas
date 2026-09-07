<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\HRM\Application\Actions\CreateWorkScheduleAction;
use Modules\HRM\Application\Actions\ListWorkSchedulesAction;
use Modules\HRM\Application\Actions\ToggleWorkScheduleStatusAction;
use Modules\HRM\Application\Actions\UpdateWorkScheduleAction;
use Modules\HRM\Http\Requests\IndexWorkScheduleRequest;
use Modules\HRM\Http\Requests\StoreWorkScheduleRequest;
use Modules\HRM\Http\Requests\UpdateWorkScheduleRequest;
use Modules\HRM\Http\Resources\WorkScheduleResource;
use Modules\HRM\Models\WorkSchedule;

class WorkScheduleController extends Controller
{
    public function index(
        IndexWorkScheduleRequest $request,
        ListWorkSchedulesAction $action,
    ): Response {
        $tenantId = app('currentTenant')->id;

        $schedules = $action->execute(
            tenantId: $tenantId,
            search: $request->search(),
            isActive: $request->isActive(),
            perPage: $request->perPage(),
        );

        $stats = [
            'total' => WorkSchedule::query()->where('tenant_id', $tenantId)->count(),
            'active' => WorkSchedule::query()->where('tenant_id', $tenantId)->where('is_active', true)->count(),
        ];

        return Inertia::render(
            'HRM/WorkSchedules/Index',
            [
                'workSchedules' => WorkScheduleResource::collection($schedules),
                'filters' => [
                    'search' => $request->search(),
                    'is_active' => $request->isActive(),
                    'per_page' => $request->perPage(),
                ],
                'stats' => $stats,
                'can' => [
                    'manage' => (bool) auth()->user()?->can('work_schedules.manage'),
                ],
            ]
        );
    }

    public function create(): Response
    {
        return Inertia::render(
            'HRM/WorkSchedules/Create',
            [
                'timezones' => timezone_identifiers_list(),
            ]
        );
    }

    public function store(
        StoreWorkScheduleRequest $request,
        CreateWorkScheduleAction $action,
    ): RedirectResponse {
        $tenantId = app('currentTenant')->id;

        $schedule = $action->execute(
            $request->toData($tenantId)
        );

        return to_route(
            'admin.hrm.work-schedules.show',
            $schedule->public_id
        )->with(
            'success',
            'Work schedule created successfully.'
        );
    }

    public function show(string $workSchedule): Response
    {
        $tenantId = app('currentTenant')->id;

        $schedule = WorkSchedule::query()
            ->forTenant($tenantId)
            ->with('days')
            ->where('public_id', $workSchedule)
            ->firstOrFail();

        return Inertia::render(
            'HRM/WorkSchedules/Show',
            [
                'workSchedule' => new WorkScheduleResource($schedule),
                'can' => [
                    'manage' => (bool) auth()->user()?->can('work_schedules.manage'),
                ],
            ]
        );
    }

    public function edit(string $workSchedule): Response
    {
        $tenantId = app('currentTenant')->id;

        $schedule = WorkSchedule::query()
            ->forTenant($tenantId)
            ->with('days')
            ->where('public_id', $workSchedule)
            ->firstOrFail();

        return Inertia::render(
            'HRM/WorkSchedules/Edit',
            [
                'workSchedule' => new WorkScheduleResource($schedule),
                'timezones' => timezone_identifiers_list(),
            ]
        );
    }

    public function update(
        UpdateWorkScheduleRequest $request,
        UpdateWorkScheduleAction $action,
        string $workSchedule,
    ): RedirectResponse {
        $tenantId = app('currentTenant')->id;

        $schedule = $action->execute(
            $request->toData(
                tenantId: $tenantId,
                publicId: $workSchedule,
            )
        );

        return to_route(
            'admin.hrm.work-schedules.show',
            $schedule->public_id
        )->with(
            'success',
            'Work schedule updated successfully.'
        );
    }

    public function toggleStatus(
        ToggleWorkScheduleStatusAction $action,
        string $workSchedule,
    ): RedirectResponse {
        $tenantId = app('currentTenant')->id;

        $schedule = $action->execute(
            tenantId: $tenantId,
            publicId: $workSchedule,
        );

        return to_route(
            'admin.hrm.work-schedules.show',
            $schedule->public_id
        )->with(
            'success',
            $schedule->is_active
                ? 'Work schedule activated successfully.'
                : 'Work schedule deactivated successfully.'
        );
    }
}
