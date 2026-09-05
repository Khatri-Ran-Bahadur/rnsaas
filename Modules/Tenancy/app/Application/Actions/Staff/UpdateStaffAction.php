<?php

namespace Modules\Tenancy\Application\Actions\Staff;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Application\DTOs\UpdateStaffData;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\Designation;
use Modules\Tenancy\Models\TenantStaff;
use RuntimeException;

final class UpdateStaffAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function execute(TenantStaff $staff, UpdateStaffData $data): TenantStaff
    {
        $tenantId = $this->currentTenant->id();

        if ($tenantId === null || $staff->tenant_id !== $tenantId) {
            abort(404);
        }

        return DB::transaction(function () use ($staff, $data, $tenantId) {
            $branch = Branch::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($data->branchId)
                ->first();

            if ($branch === null) {
                throw new RuntimeException('Selected branch is not available for this organization.');
            }

            $department = Department::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($data->departmentId)
                ->first();

            if ($department === null) {
                throw new RuntimeException('Selected department is not available for this organization.');
            }

            $designation = Designation::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($data->designationId)
                ->first();

            if ($designation === null) {
                throw new RuntimeException('Selected designation is not available for this organization.');
            }

            $codeConflict = TenantStaff::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_code', $data->employeeCode)
                ->where('id', '!=', $staff->id)
                ->exists();

            if ($codeConflict) {
                throw new RuntimeException('Employee code is already in use by another staff member.');
            }

            if ($staff->user !== null) {
                $staff->user->update([
                    'name' => $data->name,
                    'phone' => $data->phone,
                ]);
            }

            $suspendedAt = $staff->suspended_at;
            $terminatedAt = $staff->terminated_at;

            if ($data->employmentStatus === EmploymentStatus::Suspended && $staff->employment_status !== EmploymentStatus::Suspended) {
                $suspendedAt = now();
            } elseif ($data->employmentStatus === EmploymentStatus::Active) {
                $suspendedAt = null;
                $terminatedAt = null;
            } elseif ($data->employmentStatus === EmploymentStatus::Terminated && $staff->employment_status !== EmploymentStatus::Terminated) {
                $terminatedAt = now();
            }

            $staff->update([
                'branch_id' => $branch->id,
                'department_id' => $department->id,
                'designation_id' => $designation->id,
                'employee_code' => $data->employeeCode,
                'joining_date' => $data->joiningDate,
                'employment_status' => $data->employmentStatus,
                'suspended_at' => $suspendedAt,
                'terminated_at' => $terminatedAt,
            ]);

            return $staff->refresh();
        });
    }
}
