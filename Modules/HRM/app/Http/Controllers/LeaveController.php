<?php

namespace Modules\HRM\Http\Controllers;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\HRM\Application\Actions\Leaves\ApproveLeaveAction;
use Modules\HRM\Application\Actions\Leaves\CreateLeaveAction;
use Modules\HRM\Application\Actions\Leaves\ListLeavesAction;
use Modules\HRM\Application\Actions\Leaves\RejectLeaveAction;
use Modules\HRM\Application\Actions\Leaves\ToggleLeaveStatusAction;
use Modules\HRM\Application\Actions\Leaves\UpdateLeaveAction;
use Modules\HRM\Http\Requests\Leaves\IndexLeaveRequest;
use Modules\HRM\Http\Requests\Leaves\RejectLeaveRequest;
use Modules\HRM\Http\Requests\Leaves\StoreLeaveRequest;
use Modules\HRM\Http\Requests\Leaves\UpdateLeaveRequest;
use Modules\HRM\Http\Resources\LeaveResource;
use Modules\HRM\Models\LeaveRequest;

class LeaveController
{
    public function index(
        IndexLeaveRequest $request,
        CurrentTenant $currentTenant,
        ListLeavesAction $action,
    ): Response {
        $leaves = $action->execute(
            $currentTenant->id(),
            $request->validated(),
        );

        return Inertia::render('HRM/Leaves/Index', [
            'leaves' => LeaveResource::collection($leaves),
            'filters' => $request->validated(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Leaves/Create');
    }

    public function store(
        StoreLeaveRequest $request,
        CurrentTenant $currentTenant,
        CreateLeaveAction $action,
    ): RedirectResponse {
        $action->execute(
            $request->toData(
                $currentTenant->id(),
                $request->user()->id,
            ),
        );

        return redirect()
            ->route('admin.hrm.leaves.index')
            ->with(
                'success',
                'Leave request created successfully.',
            );
    }

    public function show(
        LeaveRequest $leave,
        CurrentTenant $currentTenant,
    ): Response {
        abort_unless(
            $leave->tenant_id === $currentTenant->id(),
            404,
        );

        $leave->load([
            'staff.user',
            'creator',
            'updater',
            'approver',
            'rejector',
        ]);

        return Inertia::render(
            'HRM/Leaves/Show',
            [
                'leave' => new LeaveResource($leave),
            ],
        );
    }

    public function edit(
        LeaveRequest $leave,
        CurrentTenant $currentTenant,
    ): Response {
        abort_unless(
            $leave->tenant_id === $currentTenant->id(),
            404,
        );

        return Inertia::render(
            'HRM/Leaves/Edit',
            [
                'leave' => new LeaveResource($leave),
            ],
        );
    }

    public function update(
        UpdateLeaveRequest $request,
        LeaveRequest $leave,
        CurrentTenant $currentTenant,
        UpdateLeaveAction $action,
    ): RedirectResponse {
        $action->execute(
            $leave,
            $request->toData(
                $currentTenant->id(),
                $request->user()->id,
            ),
        );

        return redirect()
            ->route(
                'admin.hrm.leaves.show',
                $leave,
            )
            ->with(
                'success',
                'Leave request updated successfully.',
            );
    }

    public function approve(
        LeaveRequest $leave,
        CurrentTenant $currentTenant,
        ApproveLeaveAction $action,
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->can('leave.manage'),
            403,
        );

        $action->execute(
            $leave,
            $currentTenant->id(),
            auth()->id(),
        );

        return back()->with(
            'success',
            'Leave request approved successfully.',
        );
    }

    public function reject(
        RejectLeaveRequest $request,
        LeaveRequest $leave,
        CurrentTenant $currentTenant,
        RejectLeaveAction $action,
    ): RedirectResponse {
        $action->execute(
            $leave,
            $currentTenant->id(),
            $request->user()->id,
            $request->reason(),
        );

        return back()->with(
            'success',
            'Leave request rejected.',
        );
    }

    public function toggleStatus(
        LeaveRequest $leave,
        CurrentTenant $currentTenant,
        ToggleLeaveStatusAction $action,
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->can('leave.manage'),
            403,
        );

        $action->execute(
            $leave,
            $currentTenant->id(),
            auth()->id(),
        );

        return back()->with(
            'success',
            'Leave status updated.',
        );
    }
}
