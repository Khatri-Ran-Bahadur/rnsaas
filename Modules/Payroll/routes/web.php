<?php

use Illuminate\Support\Facades\Route;
use Modules\Payroll\Http\Controllers\EmployeePayrollController;
use Modules\Payroll\Http\Controllers\LoanAdvanceController;
use Modules\Payroll\Http\Controllers\PayrollComponentController;
use Modules\Payroll\Http\Controllers\PayrollDashboardController;
use Modules\Payroll\Http\Controllers\PayrollGroupController;
use Modules\Payroll\Http\Controllers\PayrollReportController;
use Modules\Payroll\Http\Controllers\PayrollRunController;
use Modules\Payroll\Http\Controllers\PayslipController;
use Modules\Payroll\Http\Controllers\StatutorySchemeController;

Route::middleware(['auth', 'tenant'])->prefix('admin/payroll')->name('admin.payroll.')->group(function () {
    // 1. Executive Dashboard
    Route::get('/', [PayrollDashboardController::class, 'index'])->name('index');
    Route::get('/dashboard', [PayrollDashboardController::class, 'index'])->name('dashboard');

    // 2. Payroll Runs Engine & Wizard
    Route::get('/runs', [PayrollRunController::class, 'index'])->name('runs.index');
    Route::get('/runs/create', [PayrollRunController::class, 'create'])->name('runs.create');
    Route::post('/runs', [PayrollRunController::class, 'store'])->name('runs.store');
    Route::get('/runs/{id}', [PayrollRunController::class, 'show'])->name('runs.show');
    Route::post('/runs/{id}/calculate', [PayrollRunController::class, 'calculate'])->name('runs.calculate');
    Route::post('/runs/{id}/approve', [PayrollRunController::class, 'approve'])->name('runs.approve');
    Route::post('/runs/{id}/finalize', [PayrollRunController::class, 'finalize'])->name('runs.finalize');
    Route::post('/runs/{id}/mark-paid', [PayrollRunController::class, 'markPaid'])->name('runs.mark-paid');

    // 3. Payslips
    Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');
    Route::get('/payslips/{id}', [PayslipController::class, 'show'])->name('payslips.show');

    // 4. Employee Payroll Profiles & Salary History
    Route::get('/employees', [EmployeePayrollController::class, 'index'])->name('employees.index');
    Route::get('/employees/{id}', [EmployeePayrollController::class, 'show'])->name('employees.show');
    Route::post('/employees/{id}/revision', [EmployeePayrollController::class, 'storeRevision'])->name('employees.revision');

    // 5. Configurable Payroll Components (Earnings, Deductions, Benefits)
    Route::get('/components', [PayrollComponentController::class, 'index'])->name('components.index');
    Route::post('/components', [PayrollComponentController::class, 'store'])->name('components.store');
    Route::put('/components/{id}', [PayrollComponentController::class, 'update'])->name('components.update');

    // 6. Payroll Groups & Pay Calendars
    Route::get('/groups', [PayrollGroupController::class, 'index'])->name('groups.index');
    Route::post('/groups', [PayrollGroupController::class, 'store'])->name('groups.store');

    // 7. Employee Loans & Salary Advances
    Route::get('/loans', [LoanAdvanceController::class, 'index'])->name('loans.index');
    Route::post('/loans', [LoanAdvanceController::class, 'store'])->name('loans.store');

    // 8. Multi-Country Statutory Schemes & Tax
    Route::get('/statutory', [StatutorySchemeController::class, 'index'])->name('statutory.index');
    Route::post('/statutory', [StatutorySchemeController::class, 'store'])->name('statutory.store');

    // 9. Payroll Reports & Statutory Filings
    Route::get('/reports', [PayrollReportController::class, 'index'])->name('reports.index');
});
