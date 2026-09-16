<?php

namespace Modules\HRM\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\HRM\Domain\Enums\AttendanceSource;
use Modules\HRM\Domain\Enums\AttendanceStatus;
use Modules\HRM\Domain\Enums\DayOfWeek;
use Modules\HRM\Domain\Enums\EmployeeDocumentStatus;
use Modules\HRM\Domain\Enums\EmployeeDocumentType;
use Modules\HRM\Domain\Enums\HolidayType;
use Modules\HRM\Domain\Enums\LeaveStatus;
use Modules\HRM\Domain\Enums\LeaveType;
use Modules\HRM\Domain\Enums\OvertimeStatus;
use Modules\HRM\Domain\Enums\OvertimeType;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\EmployeeDocument;
use Modules\HRM\Models\Holiday;
use Modules\HRM\Models\LeaveRequest;
use Modules\HRM\Models\Overtime;
use Modules\HRM\Models\Shift;
use Modules\HRM\Models\WorkSchedule;
use Modules\HRM\Models\WorkScheduleDay;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantStaff;

class HRMDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(?int $targetTenantId = null): void
    {
        $tenants = $targetTenantId
            ? Tenant::where('id', $targetTenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            return;
        }

        $systemUser = User::first();

        foreach ($tenants as $tenant) {
            $tenantId = $tenant->id;

            // 1. Shifts
            $shifts = [
                [
                    'code' => 'SHIFT-GEN',
                    'name' => 'General Day Shift',
                    'description' => 'Standard corporate business hours',
                    'start_time' => '09:00:00',
                    'end_time' => '18:00:00',
                    'break_minutes' => 60,
                    'late_grace_minutes' => 15,
                    'early_leave_grace_minutes' => 10,
                    'is_overnight' => false,
                    'is_active' => true,
                ],
                [
                    'code' => 'SHIFT-MRN',
                    'name' => 'Retail Morning Shift',
                    'description' => 'Opening shift for branch outlets',
                    'start_time' => '08:00:00',
                    'end_time' => '16:30:00',
                    'break_minutes' => 45,
                    'late_grace_minutes' => 10,
                    'early_leave_grace_minutes' => 5,
                    'is_overnight' => false,
                    'is_active' => true,
                ],
                [
                    'code' => 'SHIFT-NIGHT',
                    'name' => 'Plant Night Shift',
                    'description' => 'Manufacturing plant 3rd shift',
                    'start_time' => '22:00:00',
                    'end_time' => '06:00:00',
                    'break_minutes' => 60,
                    'late_grace_minutes' => 15,
                    'early_leave_grace_minutes' => 15,
                    'is_overnight' => true,
                    'is_active' => true,
                ],
            ];

            foreach ($shifts as $s) {
                Shift::updateOrCreate(
                    ['tenant_id' => $tenantId, 'code' => $s['code']],
                    $s
                );
            }

            // 2. Work Schedules
            $schedule = WorkSchedule::updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => 'Standard 40-Hour Work Week'],
                [
                    'description' => 'Monday to Friday corporate working schedule',
                    'timezone' => $tenant->timezone ?? 'Asia/Kuala_Lumpur',
                    'default_start_time' => '09:00:00',
                    'default_end_time' => '18:00:00',
                    'break_minutes' => 60,
                    'late_grace_minutes' => 15,
                    'early_leave_grace_minutes' => 10,
                    'is_active' => true,
                ]
            );

            // Seed Schedule Days 1-7 (Mon-Sun)
            for ($day = 1; $day <= 7; $day++) {
                $isWorkDay = $day <= 5;
                WorkScheduleDay::updateOrCreate(
                    [
                        'work_schedule_id' => $schedule->id,
                        'day_of_week' => DayOfWeek::from($day),
                    ],
                    [
                        'is_working_day' => $isWorkDay,
                        'start_time' => $isWorkDay ? '09:00:00' : null,
                        'end_time' => $isWorkDay ? '18:00:00' : null,
                        'break_minutes' => $isWorkDay ? 60 : 0,
                    ]
                );
            }

            // 3. Holidays
            $currentYear = Carbon::now()->year;
            $holidays = [
                ['name' => "New Year's Day", 'start' => "{$currentYear}-01-01", 'end' => "{$currentYear}-01-01", 'type' => HolidayType::PUBLIC],
                ['name' => 'Labor Day', 'start' => "{$currentYear}-05-01", 'end' => "{$currentYear}-05-01", 'type' => HolidayType::PUBLIC],
                ['name' => 'National Day', 'start' => "{$currentYear}-08-31", 'end' => "{$currentYear}-08-31", 'type' => HolidayType::PUBLIC],
                ['name' => 'Company Annual Gala & Hackathon', 'start' => "{$currentYear}-10-15", 'end' => "{$currentYear}-10-16", 'type' => HolidayType::COMPANY],
                ['name' => 'Christmas Day', 'start' => "{$currentYear}-12-25", 'end' => "{$currentYear}-12-25", 'type' => HolidayType::PUBLIC],
            ];

            foreach ($holidays as $h) {
                Holiday::updateOrCreate(
                    ['tenant_id' => $tenantId, 'name' => $h['name']],
                    [
                        'start_date' => $h['start'],
                        'end_date' => $h['end'],
                        'type' => $h['type'],
                        'description' => $h['name'].' observed nationwide/company wide',
                        'is_recurring' => true,
                        'is_active' => true,
                    ]
                );
            }

            // 4. Staff-dependent data (Attendance, Leaves, Overtime, Documents)
            $staffList = TenantStaff::where('tenant_id', $tenantId)->get();
            if ($staffList->isNotEmpty()) {
                foreach ($staffList as $staff) {
                    // Seed Attendance for past 5 working days
                    for ($offset = 1; $offset <= 5; $offset++) {
                        $date = Carbon::now()->subDays($offset);
                        if ($date->isWeekend()) {
                            continue;
                        }

                        $isLate = ($offset === 2);
                        Attendance::updateOrCreate(
                            [
                                'tenant_id' => $tenantId,
                                'tenant_staff_id' => $staff->id,
                                'attendance_date' => $date->format('Y-m-d'),
                            ],
                            [
                                'check_in' => $isLate ? '09:28:00' : '08:55:00',
                                'check_out' => '18:15:00',
                                'worked_minutes' => $isLate ? 467 : 500,
                                'late_minutes' => $isLate ? 28 : 0,
                                'early_leave_minutes' => 0,
                                'overtime_minutes' => 15,
                                'status' => $isLate ? AttendanceStatus::LATE : AttendanceStatus::PRESENT,
                                'source' => AttendanceSource::WEB,
                                'notes' => $isLate ? 'Traffic congestion on transit line' : 'Regular on-time checkin',
                                'created_by' => $systemUser?->id,
                                'is_active' => true,
                            ]
                        );
                    }

                    // Seed Leave Requests
                    LeaveRequest::firstOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'tenant_staff_id' => $staff->id,
                            'start_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
                        ],
                        [
                            'leave_type' => LeaveType::ANNUAL,
                            'end_date' => Carbon::now()->addDays(12)->format('Y-m-d'),
                            'total_days' => 3.00,
                            'reason' => 'Annual family vacation leave request',
                            'status' => LeaveStatus::APPROVED,
                            'created_by' => $staff->user_id,
                            'approved_by' => $systemUser?->id,
                            'approved_at' => now(),
                            'is_active' => true,
                        ]
                    );

                    // Seed Overtime
                    Overtime::firstOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'tenant_staff_id' => $staff->id,
                            'date' => Carbon::now()->subDays(3)->format('Y-m-d'),
                        ],
                        [
                            'start_time' => '18:00:00',
                            'end_time' => '20:30:00',
                            'total_minutes' => 150,
                            'type' => OvertimeType::REGULAR,
                            'status' => OvertimeStatus::APPROVED,
                            'rate_multiplier' => 1.50,
                            'reason' => 'Quarterly financial report preparation and month-end ledger audits',
                            'created_by' => $staff->user_id,
                            'approved_by' => $systemUser?->id,
                            'approved_at' => now(),
                            'is_active' => true,
                        ]
                    );

                    // Seed Employee Documents
                    EmployeeDocument::firstOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'tenant_staff_id' => $staff->id,
                            'type' => EmployeeDocumentType::EMPLOYMENT_CONTRACT,
                        ],
                        [
                            'title' => 'Permanent Employment Agreement',
                            'document_number' => 'DOC-CNT-'.str_pad((string) $staff->id, 4, '0', STR_PAD_LEFT),
                            'issue_date' => $staff->joining_date ?? '2023-01-15',
                            'file_disk' => 'local',
                            'file_path' => 'documents/contracts/sample_contract.pdf',
                            'original_file_name' => 'employment_contract_signed.pdf',
                            'mime_type' => 'application/pdf',
                            'file_size' => 1024 * 320,
                            'status' => EmployeeDocumentStatus::VERIFIED,
                            'notes' => 'Counter-signed and fully verified by HR Operations',
                            'created_by' => $systemUser?->id,
                            'verified_by' => $systemUser?->id,
                            'verified_at' => now(),
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
