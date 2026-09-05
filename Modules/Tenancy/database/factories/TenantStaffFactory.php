<?php

namespace Modules\Tenancy\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\Designation;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantStaff;

/**
 * @extends Factory<TenantStaff>
 */
class TenantStaffFactory extends Factory
{
    protected $model = TenantStaff::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'department_id' => Department::factory(),
            'designation_id' => Designation::factory(),
            'employee_code' => strtoupper(fake()->unique()->bothify('EMP-###')),
            'joining_date' => fake()->date(),
            'employment_status' => EmploymentStatus::Active,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => EmploymentStatus::Active,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => EmploymentStatus::Suspended,
            'suspended_at' => now(),
        ]);
    }
}
