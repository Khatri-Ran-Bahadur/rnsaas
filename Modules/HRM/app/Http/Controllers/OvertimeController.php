<?php

namespace Modules\HRM\Http\Controllers;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\HRM\Application\Actions\Overtimes\ApproveOvertimeAction;
use Modules\HRM\Application\Actions\Overtimes\CreateOvertimeAction;
use Modules\HRM\Application\Actions\Overtimes\ListOvertimesAction;
use Modules\HRM\Application\Actions\Overtimes\RejectOvertimeAction;
use Modules\HRM\Application\Actions\Overtimes\ToggleOvertimeStatusAction;
use Modules\HRM\Application\Actions\Overtimes\UpdateOvertimeAction;
use Modules\HRM\Domain\Enums\OvertimeStatus;
use Modules\HRM\Domain\Enums\OvertimeType;
use Modules\HRM\Http\Requests\IndexOvertimeRequest;
use Modules\HRM\Http\Requests\StoreOvertimeRequest;
use Modules\HRM\Http\Requests\UpdateOvertimeRequest;
use Modules\HRM\Http\Resources\OvertimeResource;
use Modules\HRM\Models\Overtime;
use Modules\Tenancy\Models\TenantStaff;

class OvertimeController
{
    public function index(
        IndexOvertimeRequest $request,
        ListOvertimesAction $action,
        CurrentTenant $currentTenant,
    ): Response {
        $tenantId = $currentTenant->id();

        $overtimes = $action->execute(
            tenantId: $tenantId,
            search: $request->input('search'),
            tenantStaffId: $request->integer('tenant_staff_id') ?: null,
            type: $request->input('type'),
            status: $request->input('status'),
            active: $request->has('active')
                ? $request->boolean('active')
                : null,
            fromDate: $request->input('from_date'),
            toDate: $request->input('to_date'),
            perPage: $request->integer('per_page', 20),
        );

        $staffMembers = TenantStaff::query()
            ->where('tenant_id', $tenantId)
            ->where('employment_status', 'active')
            ->with('user:id,name,email')
            ->orderBy('employee_code')
            ->get(['id', 'public_id', 'user_id', 'employee_code'])
            ->map(fn ($staff) => [
                'id' => $staff->id,
                'public_id' => $staff->public_id,
                'name' => $staff->user?->name ?? 'Staff #'.$staff->employee_code,
                'employee_code' => $staff->employee_code,
            ]);

        $types = collect(OvertimeType::cases())->map(fn ($t) => [
            'value' => $t->value,
            'label' => $t->label(),
        ]);

        $statuses = collect(OvertimeStatus::cases())->map(fn ($s) => [
            'value' => $s->value,
            'label' => $s->label(),
        ]);

        $stats = [
            'total' => Overtime::query()->where('tenant_id', $tenantId)->count(),
            'pending' => Overtime::query()->where('tenant_id', $tenantId)->where('status', OvertimeStatus::PENDING)->count(),
            'approved' => Overtime::query()->where('tenant_id', $tenantId)->where('status', OvertimeStatus::APPROVED)->count(),
            'total_minutes' => (int) Overtime::query()->where('tenant_id', $tenantId)->sum('total_minutes'),
        ];

        return Inertia::render('HRM/Overtimes/Index', [
            'overtimes' => OvertimeResource::collection($overtimes),
            'filters' => $request->only([
                'search',
                'tenant_staff_id',
                'type',
                'status',
                'active',
                'from_date',
                'to_date',
                'per_page',
            ]),
            'staff_members' => $staffMembers,
            'types' => $types,
            'statuses' => $statuses,
            'stats' => $stats,
            'can' => [
                'manage' => (bool) auth()->user()?->can('overtime.manage'),
            ],
        ]);
    }

    public function create(CurrentTenant $currentTenant): Response
    {
        $tenantId = $currentTenant->id();

        $staffMembers = TenantStaff::query()
            ->where('tenant_id', $tenantId)
            ->where('employment_status', 'active')
            ->with('user:id,name,email')
            ->orderBy('employee_code')
            ->get(['id', 'public_id', 'user_id', 'employee_code'])
            ->map(fn ($staff) => [
                'id' => $staff->id,
                'public_id' => $staff->public_id,
                'name' => $staff->user?->name ?? 'Staff #'.$staff->employee_code,
                'employee_code' => $staff->employee_code,
            ]);

        $types = collect(OvertimeType::cases())->map(fn ($t) => [
            'value' => $t->value,
            'label' => $t->label(),
        ]);

        return Inertia::render('HRM/Overtimes/Create', [
            'staff_members' => $staffMembers,
            'types' => $types,
        ]);
    }

    public function store(
        StoreOvertimeRequest $request,
        CreateOvertimeAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        $overtime = $action->execute(
            $request->toData(
                tenantId: $currentTenant->id(),
                createdBy: auth()->id(),
            )
        );

        return redirect()
            ->route(
                'admin.hrm.overtimes.show',
                $overtime
            )
            ->with(
                'success',
                'Overtime created successfully.'
            );
    }

    public function show(
        Overtime $overtime,
        CurrentTenant $currentTenant,
    ): Response {
        abort_unless(
            $overtime->tenant_id === $currentTenant->id(),
            404
        );

        $overtime->load([
            'staff.user',
            'creator',
            'updater',
            'approver',
            'rejector',
        ]);

        return Inertia::render('HRM/Overtimes/Show', [
            'overtime' => new OvertimeResource($overtime),
        ]);
    }

    public function edit(
        Overtime $overtime,
        CurrentTenant $currentTenant,
    ): Response {
        abort_unless(
            $overtime->tenant_id === $currentTenant->id(),
            404
        );

        $tenantId = $currentTenant->id();

        $staffMembers = TenantStaff::query()
            ->where('tenant_id', $tenantId)
            ->where('employment_status', 'active')
            ->with('user:id,name,email')
            ->orderBy('employee_code')
            ->get(['id', 'public_id', 'user_id', 'employee_code'])
            ->map(fn ($staff) => [
                'id' => $staff->id,
                'public_id' => $staff->public_id,
                'name' => $staff->user?->name ?? 'Staff #'.$staff->employee_code,
                'employee_code' => $staff->employee_code,
            ]);

        $types = collect(OvertimeType::cases())->map(fn ($t) => [
            'value' => $t->value,
            'label' => $t->label(),
        ]);

        return Inertia::render('HRM/Overtimes/Edit', [
            'overtime' => new OvertimeResource($overtime),
            'staff_members' => $staffMembers,
            'types' => $types,
        ]);
    }

    public function update(
        UpdateOvertimeRequest $request,
        Overtime $overtime,
        UpdateOvertimeAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        abort_unless(
            $overtime->tenant_id === $currentTenant->id(),
            404
        );

        $action->execute(
            $overtime,
            $request->toData(
                updatedBy: auth()->id()
            )
        );

        return redirect()
            ->route(
                'admin.hrm.overtimes.show',
                $overtime
            )
            ->with(
                'success',
                'Overtime updated successfully.'
            );
    }

    public function toggleStatus(
        Overtime $overtime,
        ToggleOvertimeStatusAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        abort_unless(
            $overtime->tenant_id === $currentTenant->id(),
            404
        );

        $action->execute(
            $overtime,
            auth()->id()
        );

        return back()->with(
            'success',
            'Overtime status updated successfully.'
        );
    }

    public function approve(
        Overtime $overtime,
        ApproveOvertimeAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        abort_unless(
            $overtime->tenant_id === $currentTenant->id(),
            404
        );

        $action->execute(
            $overtime,
            auth()->id()
        );

        return back()->with(
            'success',
            'Overtime approved successfully.'
        );
    }

    public function reject(
        Overtime $overtime,
        RejectOvertimeAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        abort_unless(
            $overtime->tenant_id === $currentTenant->id(),
            404
        );

        $reason = request()->validate([
            'rejection_reason' => [
                'required',
                'string',
                'max:500',
            ],
        ])['rejection_reason'];

        $action->execute(
            $overtime,
            auth()->id(),
            $reason
        );

        return back()->with(
            'success',
            'Overtime rejected successfully.'
        );
    }
}
