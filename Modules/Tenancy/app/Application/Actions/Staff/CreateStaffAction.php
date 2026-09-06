<?php

namespace Modules\Tenancy\Application\Actions\Staff;

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Tenancy\Application\DTOs\CreateStaffData;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\Designation;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantStaff;
use RuntimeException;

final class CreateStaffAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(CreateStaffData $data): TenantStaff
    {
        return $this->execute($data);
    }

    public function execute(CreateStaffData $data): TenantStaff
    {
        $tenantId = $this->currentTenant->id();

        if ($tenantId === null) {
            throw new RuntimeException('Current tenant is not resolved.');
        }

        return DB::transaction(function () use ($data, $tenantId) {

            $branch = Branch::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($data->branchId)
                ->active()
                ->first();

            if ($branch === null) {
                throw new RuntimeException(
                    'Selected branch is not available for this organization.'
                );
            }

            $department = Department::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($data->departmentId)
                ->active()
                ->first();

            if ($department === null) {
                throw new RuntimeException(
                    'Selected department is not available for this organization.'
                );
            }

            $designation = Designation::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($data->designationId)
                ->active()
                ->first();

            if ($designation === null) {
                throw new RuntimeException(
                    'Selected designation is not available for this organization.'
                );
            }

            if ($data->userId !== null) {
                $user = User::query()->findOrFail($data->userId);

                $membership = TenantMembership::query()
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $user->id)
                    ->first();

                if ($membership === null || $membership->status !== TenantMembershipStatus::Active) {
                    throw new RuntimeException(
                        'This user does not have an active membership in this organization.'
                    );
                }
            } else {
                $user = User::query()
                    ->where('email', $data->email)
                    ->first();

                if ($user === null) {
                    $user = User::query()->create([
                        'name' => $data->name,
                        'email' => $data->email,
                        'password' => Hash::make(Str::random(64)),
                    ]);
                }

                $membership = TenantMembership::query()
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $user->id)
                    ->first();

                if ($membership === null) {
                    $membership = TenantMembership::query()->create([
                        'tenant_id' => $tenantId,
                        'user_id' => $user->id,
                        'status' => TenantMembershipStatus::Active,
                    ]);
                } elseif ($membership->status !== TenantMembershipStatus::Active) {
                    throw new RuntimeException(
                        'This user does not have an active membership in this organization.'
                    );
                }
            }

            $existingStaff = TenantStaff::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->exists();

            if ($existingStaff) {
                throw new RuntimeException(
                    'This user is already a staff member of this organization.'
                );
            }

            $employeeCodeExists = TenantStaff::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_code', $data->employeeCode)
                ->exists();

            if ($employeeCodeExists) {
                throw new RuntimeException(
                    'Employee code is already in use.'
                );
            }

            return TenantStaff::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'branch_id' => $branch->id,
                'department_id' => $department->id,
                'designation_id' => $designation->id,
                'employee_code' => $data->employeeCode,
                'joining_date' => $data->joiningDate,
                'employment_status' => $data->employmentStatus,
                'metadata' => $data->phone ? ['phone' => $data->phone] : null,
            ]);
        });
    }
}
