<?php

namespace Modules\Admin\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Tenancy\Domain\Enums\BranchStatus;
use Modules\Tenancy\Domain\Enums\DepartmentStatus;
use Modules\Tenancy\Domain\Enums\DesignationStatus;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\Designation;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantStaff;

class AdminDatabaseSeeder extends Seeder
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

        foreach ($tenants as $tenant) {
            $tenantId = $tenant->id;

            // 1. Branches
            $branches = [
                [
                    'code' => 'HQ-01',
                    'name' => 'HQ - Main Corporate Office',
                    'status' => BranchStatus::Active,
                    'address_line_1' => 'Suite 18-A, Menara Corporate Tower',
                    'city' => 'Kuala Lumpur',
                    'state' => 'Wilayah Persekutuan',
                    'postal_code' => '50450',
                    'country_code' => 'MY',
                ],
                [
                    'code' => 'BR-02',
                    'name' => 'Downtown Flagship Outlet',
                    'status' => BranchStatus::Active,
                    'address_line_1' => 'Lot G-42, Grand Commercial Arcade',
                    'city' => 'Kuala Lumpur',
                    'state' => 'Wilayah Persekutuan',
                    'postal_code' => '50100',
                    'country_code' => 'MY',
                ],
                [
                    'code' => 'BR-03',
                    'name' => 'West Logistics & Fulfillment Center',
                    'status' => BranchStatus::Active,
                    'address_line_1' => 'Plot 88, Subang Industrial Park',
                    'city' => 'Shah Alam',
                    'state' => 'Selangor',
                    'postal_code' => '40000',
                    'country_code' => 'MY',
                ],
            ];

            $branchModels = [];
            foreach ($branches as $b) {
                $branchModels[$b['code']] = Branch::updateOrCreate(
                    ['tenant_id' => $tenantId, 'code' => $b['code']],
                    $b
                );
            }

            // 2. Departments
            $departments = [
                ['code' => 'EXEC', 'name' => 'Executive & General Management', 'status' => DepartmentStatus::Active],
                ['code' => 'FIN', 'name' => 'Finance & Accounting', 'status' => DepartmentStatus::Active],
                ['code' => 'HR', 'name' => 'Human Resource & Administration', 'status' => DepartmentStatus::Active],
                ['code' => 'OPS', 'name' => 'Production & Operations', 'status' => DepartmentStatus::Active],
                ['code' => 'SALES', 'name' => 'Sales & Business Development', 'status' => DepartmentStatus::Active],
            ];

            $deptModels = [];
            foreach ($departments as $d) {
                $deptModels[$d['code']] = Department::updateOrCreate(
                    ['tenant_id' => $tenantId, 'code' => $d['code']],
                    $d
                );
            }

            // 3. Designations
            $designations = [
                ['code' => 'MD', 'name' => 'Managing Director', 'status' => DesignationStatus::Active],
                ['code' => 'CFO', 'name' => 'Chief Financial Officer', 'status' => DesignationStatus::Active],
                ['code' => 'HRM', 'name' => 'HR Operations Manager', 'status' => DesignationStatus::Active],
                ['code' => 'OPS-MGR', 'name' => 'Plant Operations Manager', 'status' => DesignationStatus::Active],
                ['code' => 'SR-ACC', 'name' => 'Senior Financial Accountant', 'status' => DesignationStatus::Active],
                ['code' => 'PROD-ENG', 'name' => 'Lead Production Engineer', 'status' => DesignationStatus::Active],
                ['code' => 'SALES-EXEC', 'name' => 'Senior Client Account Executive', 'status' => DesignationStatus::Active],
            ];

            $desigModels = [];
            foreach ($designations as $ds) {
                $desigModels[$ds['code']] = Designation::updateOrCreate(
                    ['tenant_id' => $tenantId, 'code' => $ds['code']],
                    $ds
                );
            }

            // 4. Sample Staff Members
            $staffData = [
                [
                    'name' => 'Alexander Wright',
                    'email' => "alex.wright+t{$tenantId}@sathisaas.com",
                    'code' => 'EMP-1001',
                    'branch' => 'HQ-01',
                    'dept' => 'EXEC',
                    'desig' => 'MD',
                    'role' => 'Owner',
                    'joined' => '2023-01-15',
                ],
                [
                    'name' => 'Elena Rostova',
                    'email' => "elena.rostova+t{$tenantId}@sathisaas.com",
                    'code' => 'EMP-1002',
                    'branch' => 'HQ-01',
                    'dept' => 'FIN',
                    'desig' => 'CFO',
                    'role' => 'Admin',
                    'joined' => '2023-03-01',
                ],
                [
                    'name' => 'Marcus Sterling',
                    'email' => "marcus.sterling+t{$tenantId}@sathisaas.com",
                    'code' => 'EMP-1003',
                    'branch' => 'HQ-01',
                    'dept' => 'HR',
                    'desig' => 'HRM',
                    'role' => 'Manager',
                    'joined' => '2023-06-10',
                ],
                [
                    'name' => 'Sofia Chen',
                    'email' => "sofia.chen+t{$tenantId}@sathisaas.com",
                    'code' => 'EMP-1004',
                    'branch' => 'BR-02',
                    'dept' => 'SALES',
                    'desig' => 'SALES-EXEC',
                    'role' => 'Staff',
                    'joined' => '2024-01-08',
                ],
                [
                    'name' => 'David O\'Connor',
                    'email' => "david.oconnor+t{$tenantId}@sathisaas.com",
                    'code' => 'EMP-1005',
                    'branch' => 'BR-03',
                    'dept' => 'OPS',
                    'desig' => 'OPS-MGR',
                    'role' => 'Manager',
                    'joined' => '2023-09-15',
                ],
            ];

            foreach ($staffData as $s) {
                $user = User::firstOrCreate(
                    ['email' => $s['email']],
                    [
                        'name' => $s['name'],
                        'password' => Hash::make('password123'),
                        'email_verified_at' => now(),
                    ]
                );

                TenantMembership::firstOrCreate(
                    ['tenant_id' => $tenantId, 'user_id' => $user->id],
                    ['role' => $s['role']]
                );

                $branchId = $branchModels[$s['branch']]->id ?? null;
                $deptId = $deptModels[$s['dept']]->id ?? null;
                $desigId = $desigModels[$s['desig']]->id ?? null;

                TenantStaff::updateOrCreate(
                    ['tenant_id' => $tenantId, 'employee_code' => $s['code']],
                    [
                        'user_id' => $user->id,
                        'branch_id' => $branchId,
                        'department_id' => $deptId,
                        'designation_id' => $desigId,
                        'joining_date' => $s['joined'],
                        'employment_status' => EmploymentStatus::Active,
                    ]
                );
            }
        }
    }
}
