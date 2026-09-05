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
use Modules\Tenancy\Models\TenantStaff;

final class CreateStaffAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(CreateStaffData $data): TenantStaff
    {
        $tenant = $this->currentTenant->get();

        return DB::transaction(function () use ($data, $tenant): TenantStaff {
            $branch = Branch::query()
                ->whereKey($data->branchId)
                ->where('tenant_id', $tenant->id)
                ->where('status', 'active')
                ->firstOrFail();

            $email = strtolower(trim($data->email));

            $user = User::query()
                ->where('email', $email)
                ->first();

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $data->name,
                    'email' => $email,
                    'phone' => $data->phone,
                    'password' => Hash::make(Str::random(40)),
                ]);
            }

            $membership = $user->tenants()
                ->whereKey($tenant->id)
                ->first();

            if ($membership === null) {
                $user->tenants()->attach($tenant->id, [
                    'status' => TenantMembershipStatus::Active->value,
                    'joined_at' => now(),
                ]);
            }

            $existingStaff = TenantStaff::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($existingStaff) {
                throw new \LogicException(
                    'This user is already a staff member of this organization.'
                );
            }

            return TenantStaff::query()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'branch_id' => $branch->id,
                'employee_code' => $data->employeeCode,
                'designation' => $data->designation,
                'base_salary' => $data->baseSalary,
                'joining_date' => $data->joiningDate,
                'employment_status' => 'active',
            ]);
        });
    }
}
