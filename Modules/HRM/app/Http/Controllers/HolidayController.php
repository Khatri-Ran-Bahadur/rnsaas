<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\HRM\Application\Actions\Holidays\CreateHolidayAction;
use Modules\HRM\Application\Actions\Holidays\ListHolidaysAction;
use Modules\HRM\Application\Actions\Holidays\ToggleHolidayStatusAction;
use Modules\HRM\Application\Actions\Holidays\UpdateHolidayAction;
use Modules\HRM\Domain\Enums\HolidayType;
use Modules\HRM\Http\Requests\IndexHolidayRequest;
use Modules\HRM\Http\Requests\StoreHolidayRequest;
use Modules\HRM\Http\Requests\UpdateHolidayRequest;
use Modules\HRM\Http\Resources\HolidayResource;
use Modules\HRM\Models\Holiday;

class HolidayController
{
    public function index(
        IndexHolidayRequest $request,
        ListHolidaysAction $action
    ): Response {
        $tenantId = app('currentTenant')->id;

        $holidays = $action->execute(
            tenantId: $tenantId,
            search: $request->input('search'),
            type: $request->input('type'),
            active: $request->has('active')
                ? $request->boolean('active')
                : null,
            fromDate: $request->input('from_date'),
            toDate: $request->input('to_date'),
            perPage: $request->integer('per_page', 20),
        );

        $types = collect(HolidayType::cases())->map(fn ($t) => [
            'value' => $t->value,
            'label' => $t->label(),
        ]);

        $stats = [
            'total' => Holiday::query()->where('tenant_id', $tenantId)->count(),
            'active' => Holiday::query()->where('tenant_id', $tenantId)->where('is_active', true)->count(),
            'upcoming' => Holiday::query()->where('tenant_id', $tenantId)->whereDate('start_date', '>=', now()->toDateString())->count(),
        ];

        return Inertia::render('HRM/Holidays/Index', [
            'holidays' => HolidayResource::collection($holidays),
            'filters' => $request->only([
                'search',
                'type',
                'active',
                'from_date',
                'to_date',
                'per_page',
            ]),
            'types' => $types,
            'stats' => $stats,
            'can' => [
                'manage' => (bool) auth()->user()?->can('holidays.manage'),
            ],
        ]);
    }

    public function create(): Response
    {
        $types = collect(HolidayType::cases())->map(fn ($t) => [
            'value' => $t->value,
            'label' => $t->label(),
        ]);

        return Inertia::render('HRM/Holidays/Create', [
            'types' => $types,
        ]);
    }

    public function store(
        StoreHolidayRequest $request,
        CreateHolidayAction $action
    ): RedirectResponse {
        $holiday = $action->execute(
            $request->toData(app('currentTenant')->id)
        );

        return redirect()
            ->route('admin.hrm.holidays.show', $holiday->public_id)
            ->with('success', 'Holiday created successfully.');
    }

    public function show(Holiday $holiday): Response
    {
        abort_unless(
            $holiday->tenant_id === app('currentTenant')->id,
            404
        );

        return Inertia::render('HRM/Holidays/Show', [
            'holiday' => new HolidayResource($holiday),
            'can' => [
                'manage' => (bool) auth()->user()?->can('holidays.manage'),
            ],
        ]);
    }

    public function edit(Holiday $holiday): Response
    {
        abort_unless(
            $holiday->tenant_id === app('currentTenant')->id,
            404
        );

        $types = collect(HolidayType::cases())->map(fn ($t) => [
            'value' => $t->value,
            'label' => $t->label(),
        ]);

        return Inertia::render('HRM/Holidays/Edit', [
            'holiday' => new HolidayResource($holiday),
            'types' => $types,
        ]);
    }

    public function update(
        UpdateHolidayRequest $request,
        Holiday $holiday,
        UpdateHolidayAction $action
    ): RedirectResponse {
        abort_unless(
            $holiday->tenant_id === app('currentTenant')->id,
            404
        );

        $action->execute(
            $holiday,
            $request->toData()
        );

        return redirect()
            ->route('admin.hrm.holidays.show', $holiday->public_id)
            ->with('success', 'Holiday updated successfully.');
    }

    public function toggleStatus(
        Holiday $holiday,
        ToggleHolidayStatusAction $action
    ): RedirectResponse {
        abort_unless(
            $holiday->tenant_id === app('currentTenant')->id,
            404
        );

        $action->execute($holiday);

        return back()->with(
            'success',
            'Holiday status updated successfully.'
        );
    }
}
