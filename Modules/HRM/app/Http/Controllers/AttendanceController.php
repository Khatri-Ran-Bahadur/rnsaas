<?php

namespace Modules\HRM\Http\Controllers;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\HRM\Application\Actions\Attendances\CreateAttendanceAction;
use Modules\HRM\Application\Actions\Attendances\ListAttendancesAction;
use Modules\HRM\Application\Actions\Attendances\ToggleAttendanceStatusAction;
use Modules\HRM\Application\Actions\Attendances\UpdateAttendanceAction;
use Modules\HRM\Http\Requests\Attendances\IndexAttendanceRequest;
use Modules\HRM\Http\Requests\Attendances\StoreAttendanceRequest;
use Modules\HRM\Http\Requests\Attendances\UpdateAttendanceRequest;
use Modules\HRM\Http\Resources\AttendanceResource;
use Modules\HRM\Models\Attendance;

class AttendanceController
{
    public function index(
        IndexAttendanceRequest $request,
        CurrentTenant $currentTenant,
        ListAttendancesAction $action,
    ): Response {
        $attendances = $action->execute(
            $currentTenant->id(),
            $request->validated(),
        );

        return Inertia::render('HRM/Attendances/Index', [
            'attendances' => AttendanceResource::collection(
                $attendances,
            ),
            'filters' => $request->validated(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render(
            'HRM/Attendances/Create',
        );
    }

    public function store(
        StoreAttendanceRequest $request,
        CurrentTenant $currentTenant,
        CreateAttendanceAction $action,
    ): RedirectResponse {
        $action->execute(
            $request->toData(
                $currentTenant->id(),
                $request->user()->id,
            ),
        );

        return redirect()
            ->route('admin.hrm.attendances.index')
            ->with(
                'success',
                'Attendance recorded successfully.',
            );
    }

    public function show(
        Attendance $attendance,
        CurrentTenant $currentTenant,
    ): Response {
        abort_unless(
            $attendance->tenant_id === $currentTenant->id(),
            404,
        );

        $attendance->load([
            'staff.user',
            'creator',
            'updater',
        ]);

        return Inertia::render(
            'HRM/Attendances/Show',
            [
                'attendance' => new AttendanceResource(
                    $attendance,
                ),
            ],
        );
    }

    public function edit(
        Attendance $attendance,
        CurrentTenant $currentTenant,
    ): Response {
        abort_unless(
            $attendance->tenant_id === $currentTenant->id(),
            404,
        );

        return Inertia::render(
            'HRM/Attendances/Edit',
            [
                'attendance' => new AttendanceResource(
                    $attendance,
                ),
            ],
        );
    }

    public function update(
        UpdateAttendanceRequest $request,
        Attendance $attendance,
        CurrentTenant $currentTenant,
        UpdateAttendanceAction $action,
    ): RedirectResponse {
        $action->execute(
            $attendance,
            $request->toData(
                $currentTenant->id(),
                $request->user()->id,
            ),
        );

        return redirect()
            ->route(
                'admin.hrm.attendances.show',
                $attendance,
            )
            ->with(
                'success',
                'Attendance updated successfully.',
            );
    }

    public function toggleStatus(
        Attendance $attendance,
        CurrentTenant $currentTenant,
        ToggleAttendanceStatusAction $action,
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->can('attendance.manage'),
            403,
        );

        $action->execute(
            $attendance,
            $currentTenant->id(),
            auth()->id(),
        );

        return back()->with(
            'success',
            'Attendance status updated.',
        );
    }
}
