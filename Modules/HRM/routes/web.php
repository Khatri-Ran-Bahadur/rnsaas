<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\HRM\Http\Controllers\AttendanceController;
use Modules\HRM\Http\Controllers\EmployeeDocumentController;
use Modules\HRM\Http\Controllers\HolidayController;
use Modules\HRM\Http\Controllers\LeaveController;
use Modules\HRM\Http\Controllers\OvertimeController;
use Modules\HRM\Http\Controllers\ShiftController;
use Modules\HRM\Http\Controllers\WorkScheduleController;

Route::middleware(['auth', 'tenant'])
    ->prefix('admin/hrm')
    ->name('admin.hrm.')
    ->group(function () {
        Route::prefix('work-schedules')
            ->name('work-schedules.')
            ->controller(WorkScheduleController::class)
            ->group(function () {
                Route::get('', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('{workSchedule}/edit', 'edit')->name('edit');
                Route::put('{workSchedule}', 'update')->name('update');
                Route::get('{workSchedule}', 'show')->name('show');
                Route::patch('{workSchedule}/toggle-status', 'toggleStatus')->name('toggle-status');
            });

        Route::prefix('shifts')
            ->name('shifts.')
            ->controller(ShiftController::class)
            ->group(function () {
                Route::get('', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('{shift}/edit', 'edit')->name('edit');
                Route::put('{shift}', 'update')->name('update');
                Route::patch('{shift}/toggle-status', 'toggleStatus')->name('toggle-status');
                Route::get('{shift}', 'show')->name('show');
            });

        Route::prefix('holidays')
            ->name('holidays.')
            ->controller(HolidayController::class)
            ->group(function () {

                Route::get('', 'index')->name('index');

                Route::get('create', 'create')->name('create');

                Route::post('/', 'store')->name('store');

                Route::get('{holiday}/edit', 'edit')->name('edit');

                Route::put('{holiday}', 'update')->name('update');

                Route::patch('{holiday}/toggle-status', 'toggleStatus'
                )->name('toggle-status');

                Route::get('{holiday}', 'show')->name('show');
            });

        Route::prefix('overtimes')
            ->name('overtimes.')
            ->controller(OvertimeController::class)
            ->group(function () {
                Route::get('', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('{overtime}/edit', 'edit')->name('edit');
                Route::put('{overtime}', 'update')->name('update');
                Route::patch('{overtime}/toggle-status', 'toggleStatus')->name('toggle-status');
                Route::patch('{overtime}/approve', 'approve')->name('approve');
                Route::patch('{overtime}/reject', 'reject')->name('reject');
                Route::get('{overtime}', 'show')->name('show');
            });

        Route::prefix('employee-documents')
            ->name('employee-documents.')
            ->controller(EmployeeDocumentController::class)
            ->group(function () {
                Route::get('', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');

                Route::get('{employeeDocument}/edit', 'edit')
                    ->name('edit');

                Route::put('{employeeDocument}', 'update')
                    ->name('update');

                Route::patch('{employeeDocument}/toggle-status', 'toggleStatus')
                    ->name('toggle-status');

                Route::delete('{employeeDocument}', 'destroy')
                    ->name('destroy');

                Route::get('{employeeDocument}/download', 'download')
                    ->name('download');

                Route::get('{employeeDocument}', 'show')
                    ->name('show');
            });

        Route::prefix('attendances')
            ->name('attendances.')
            ->controller(AttendanceController::class)
            ->group(function () {
                Route::get('', 'index')->name('index');

                Route::get('create', 'create')->name('create');

                Route::post('/', 'store')->name('store');

                Route::get('{attendance}/edit', 'edit')
                    ->name('edit');

                Route::put('{attendance}', 'update')
                    ->name('update');

                Route::patch(
                    '{attendance}/toggle-status',
                    'toggleStatus',
                )->name('toggle-status');

                Route::get('{attendance}', 'show')
                    ->name('show');
            });

        Route::prefix('leaves')
            ->name('leaves.')
            ->controller(LeaveController::class)
            ->group(function () {
                Route::get('', 'index')->name('index');

                Route::get('create', 'create')->name('create');

                Route::post('/', 'store')->name('store');

                Route::get('{leave}/edit', 'edit')
                    ->name('edit');

                Route::put('{leave}', 'update')
                    ->name('update');

                Route::patch('{leave}/approve', 'approve')
                    ->name('approve');

                Route::patch('{leave}/reject', 'reject')
                    ->name('reject');

                Route::patch(
                    '{leave}/toggle-status',
                    'toggleStatus',
                )->name('toggle-status');

                Route::get('{leave}', 'show')
                    ->name('show');
            });

        Route::get('overview', fn () => Inertia::render('HRM/Overview/Index'))
            ->name('overview');

        Route::get('reports', fn () => Inertia::render('HRM/Reports/Index'))
            ->name('reports');

        Route::get('exceptions', fn () => Inertia::render('HRM/Exceptions/Index'))
            ->name('exceptions');
    });
