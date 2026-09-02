<?php

namespace Modules\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Subscription\Models\Feature;

class SubscriptionFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            // Accounting
            [
                'name' => 'Accounting',
                'slug' => 'accounting',
                'module' => 'accounting',
                'description' => 'Core accounting functionality.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Invoices',
                'slug' => 'accounting.invoices',
                'module' => 'accounting',
                'description' => 'Create and manage invoices.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Expenses',
                'slug' => 'accounting.expenses',
                'module' => 'accounting',
                'description' => 'Track and manage expenses.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Reports',
                'slug' => 'accounting.reports',
                'module' => 'accounting',
                'description' => 'Access accounting reports.',
                'sort_order' => 4,
            ],

            // Inventory
            [
                'name' => 'Inventory',
                'slug' => 'inventory',
                'module' => 'inventory',
                'description' => 'Core inventory functionality.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Products',
                'slug' => 'inventory.products',
                'module' => 'inventory',
                'description' => 'Manage inventory products.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Stock',
                'slug' => 'inventory.stock',
                'module' => 'inventory',
                'description' => 'Manage stock and inventory movements.',
                'sort_order' => 3,
            ],

            // HRM
            [
                'name' => 'HRM',
                'slug' => 'hrm',
                'module' => 'hrm',
                'description' => 'Core human resource management.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Employees',
                'slug' => 'hrm.employees',
                'module' => 'hrm',
                'description' => 'Manage employees.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Attendance',
                'slug' => 'hrm.attendance',
                'module' => 'hrm',
                'description' => 'Manage employee attendance.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Leave',
                'slug' => 'hrm.leave',
                'module' => 'hrm',
                'description' => 'Manage employee leave.',
                'sort_order' => 4,
            ],

            // POS
            [
                'name' => 'POS',
                'slug' => 'pos',
                'module' => 'pos',
                'description' => 'Point of sale functionality.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Sales',
                'slug' => 'pos.sales',
                'module' => 'pos',
                'description' => 'Manage point of sale sales.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Products',
                'slug' => 'pos.products',
                'module' => 'pos',
                'description' => 'Manage POS products.',
                'sort_order' => 3,
            ],

            // Payroll
            [
                'name' => 'Payroll',
                'slug' => 'payroll',
                'module' => 'payroll',
                'description' => 'Core payroll functionality.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Salary',
                'slug' => 'payroll.salary',
                'module' => 'payroll',
                'description' => 'Manage salary processing.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Payslip',
                'slug' => 'payroll.payslip',
                'module' => 'payroll',
                'description' => 'Generate and manage payslips.',
                'sort_order' => 3,
            ],
        ];

        foreach ($features as $feature) {
            Feature::firstOrCreate(
                [
                    'slug' => $feature['slug'],
                ],
                [
                    'public_id' => (string) Str::ulid(),
                    'name' => $feature['name'],
                    'module' => $feature['module'],
                    'description' => $feature['description'],
                    'is_active' => true,
                    'sort_order' => $feature['sort_order'],
                ],
            );
        }
    }
}
