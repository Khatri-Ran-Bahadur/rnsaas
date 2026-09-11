<?php

namespace Modules\Tenancy\Domain\Constants;

final class OrganizationPermissions
{
    // General & Dashboard
    public const DASHBOARD_VIEW = 'dashboard.view';

    public const COMPANY_PROFILE_VIEW = 'company_profile.view';

    public const COMPANY_PROFILE_UPDATE = 'company_profile.update';

    // Organization Structure
    public const BRANCHES_VIEW = 'branches.view';

    public const BRANCHES_MANAGE = 'branches.manage';

    public const DEPARTMENTS_VIEW = 'departments.view';

    public const DEPARTMENTS_MANAGE = 'departments.manage';

    public const DESIGNATIONS_VIEW = 'designations.view';

    public const DESIGNATIONS_MANAGE = 'designations.manage';

    // Users & Access
    public const MEMBERS_VIEW = 'members.view';

    public const MEMBERS_INVITE = 'members.invite';

    public const MEMBERS_MANAGE = 'members.manage';

    public const ROLES_VIEW = 'roles.view';

    public const ROLES_MANAGE = 'roles.manage';

    public const INVITATIONS_VIEW = 'invitations.view';

    public const INVITATIONS_MANAGE = 'invitations.manage';

    // HRM & Staff
    public const STAFF_VIEW = 'staff.view';

    public const STAFF_MANAGE = 'staff.manage';

    public const WORK_SCHEDULES_VIEW = 'work_schedules.view';

    public const WORK_SCHEDULES_MANAGE = 'work_schedules.manage';

    public const SHIFTS_VIEW = 'shifts.view';

    public const SHIFTS_MANAGE = 'shifts.manage';

    public const HOLIDAYS_VIEW = 'holidays.view';

    public const HOLIDAYS_MANAGE = 'holidays.manage';

    public const ATTENDANCE_VIEW = 'attendance.view';

    public const ATTENDANCE_MANAGE = 'attendance.manage';

    public const LEAVE_VIEW = 'leave.view';

    public const LEAVE_MANAGE = 'leave.manage';

    public const OVERTIME_VIEW = 'overtime.view';

    public const OVERTIME_MANAGE = 'overtime.manage';

    public const EMPLOYEE_DOCUMENTS_VIEW = 'employee_documents.view';

    public const EMPLOYEE_DOCUMENTS_MANAGE = 'employee_documents.manage';

    public const HR_REPORTS_VIEW = 'hr_reports.view';

    // Payroll
    public const PAYROLL_VIEW = 'payroll.view';

    public const PAYROLL_MANAGE = 'payroll.manage';

    public const ACCOUNTING_VIEW = 'accounting.view';

    public const ACCOUNTING_MANAGE = 'accounting.manage';

    public const ACCOUNTING_CUSTOMERS_MANAGE = 'accounting.customers.manage';

    public const ACCOUNTING_INVOICES_MANAGE = 'accounting.invoices.manage';

    public const ACCOUNTING_VENDORS_MANAGE = 'accounting.vendors.manage';

    public const ACCOUNTING_BILLS_MANAGE = 'accounting.bills.manage';

    public const ACCOUNTING_PAYMENTS_MANAGE = 'accounting.payments.manage';

    public const ACCOUNTING_REPORTS_VIEW = 'accounting.reports.view';

    /**
     * @return array<string, array{
     *     label: string,
     *     permissions: array<string, array{
     *         label: string,
     *         description: string
     *     }>
     * }>
     */
    public static function grouped(): array
    {
        return [
            'General & Profile' => [
                'label' => 'General & Profile',
                'permissions' => [
                    self::DASHBOARD_VIEW => [
                        'label' => 'View Dashboard',
                        'description' => 'Can access and view organization dashboard metrics.',
                    ],
                    self::COMPANY_PROFILE_VIEW => [
                        'label' => 'View Company Profile',
                        'description' => 'Can view organization profile and settings.',
                    ],
                    self::COMPANY_PROFILE_UPDATE => [
                        'label' => 'Manage Company Profile',
                        'description' => 'Can edit and update organization company profile.',
                    ],
                ],
            ],

            'Organization Structure' => [
                'label' => 'Organization Structure',
                'permissions' => [
                    self::BRANCHES_VIEW => [
                        'label' => 'View Branches',
                        'description' => 'Can browse and view organization branches.',
                    ],
                    self::BRANCHES_MANAGE => [
                        'label' => 'Manage Branches',
                        'description' => 'Can create, edit, activate, and deactivate branches.',
                    ],
                    self::DEPARTMENTS_VIEW => [
                        'label' => 'View Departments',
                        'description' => 'Can browse and view departments.',
                    ],
                    self::DEPARTMENTS_MANAGE => [
                        'label' => 'Manage Departments',
                        'description' => 'Can create, edit, activate, and deactivate departments.',
                    ],
                    self::DESIGNATIONS_VIEW => [
                        'label' => 'View Designations',
                        'description' => 'Can browse and view job titles/designations.',
                    ],
                    self::DESIGNATIONS_MANAGE => [
                        'label' => 'Manage Designations',
                        'description' => 'Can create, edit, activate, and deactivate designations.',
                    ],
                ],
            ],

            'Users & Access' => [
                'label' => 'Users & Access',
                'permissions' => [
                    self::MEMBERS_VIEW => [
                        'label' => 'View Members',
                        'description' => 'Can browse organization members.',
                    ],
                    self::MEMBERS_INVITE => [
                        'label' => 'Invite Members',
                        'description' => 'Can send invitations to new or existing users.',
                    ],
                    self::MEMBERS_MANAGE => [
                        'label' => 'Manage Members',
                        'description' => 'Can update member roles, suspend, reactivate, and revoke access.',
                    ],
                    self::ROLES_VIEW => [
                        'label' => 'View Roles & Permissions',
                        'description' => 'Can view organization-specific roles and permission assignments.',
                    ],
                    self::ROLES_MANAGE => [
                        'label' => 'Manage Roles & Permissions',
                        'description' => 'Can create, edit, and delete organization roles and assign permissions.',
                    ],
                    self::INVITATIONS_VIEW => [
                        'label' => 'View Invitations',
                        'description' => 'Can browse sent and pending invitations.',
                    ],
                    self::INVITATIONS_MANAGE => [
                        'label' => 'Manage Invitations',
                        'description' => 'Can resend or revoke organization invitations.',
                    ],
                ],
            ],

            'HRM & Staff' => [
                'label' => 'HRM & Staff',
                'permissions' => [
                    self::STAFF_VIEW => [
                        'label' => 'View Staff Directory',
                        'description' => 'Can view organization employee directory.',
                    ],
                    self::STAFF_MANAGE => [
                        'label' => 'Manage Staff',
                        'description' => 'Can create, edit, suspend, reactivate, and manage staff profiles.',
                    ],

                    self::WORK_SCHEDULES_VIEW => [
                        'label' => 'View Work Schedules',
                        'description' => 'Can view employee work schedules and working-time configurations.',
                    ],
                    self::WORK_SCHEDULES_MANAGE => [
                        'label' => 'Manage Work Schedules',
                        'description' => 'Can create, edit, activate, and deactivate work schedules.',
                    ],

                    self::SHIFTS_VIEW => [
                        'label' => 'View Shifts',
                        'description' => 'Can view organization shifts and shift assignments.',
                    ],
                    self::SHIFTS_MANAGE => [
                        'label' => 'Manage Shifts',
                        'description' => 'Can create, edit, activate, deactivate, and assign shifts.',
                    ],

                    self::HOLIDAYS_VIEW => [
                        'label' => 'View Holidays',
                        'description' => 'Can view organization holidays and holiday calendars.',
                    ],
                    self::HOLIDAYS_MANAGE => [
                        'label' => 'Manage Holidays',
                        'description' => 'Can create, edit, and manage organization holidays.',
                    ],

                    self::ATTENDANCE_VIEW => [
                        'label' => 'View Attendance',
                        'description' => 'Can view staff attendance logs and records.',
                    ],
                    self::ATTENDANCE_MANAGE => [
                        'label' => 'Manage Attendance',
                        'description' => 'Can record, edit, adjust, and approve attendance entries.',
                    ],

                    self::LEAVE_VIEW => [
                        'label' => 'View Leave Requests',
                        'description' => 'Can view submitted employee leave applications.',
                    ],
                    self::LEAVE_MANAGE => [
                        'label' => 'Manage Leave Requests',
                        'description' => 'Can approve, reject, and manage employee leave applications.',
                    ],

                    self::OVERTIME_VIEW => [
                        'label' => 'View Overtime',
                        'description' => 'Can view employee overtime records and summaries.',
                    ],
                    self::OVERTIME_MANAGE => [
                        'label' => 'Manage Overtime',
                        'description' => 'Can record, edit, approve, and manage employee overtime.',
                    ],

                    self::EMPLOYEE_DOCUMENTS_VIEW => [
                        'label' => 'View Employee Documents',
                        'description' => 'Can view employee documents and employment-related files.',
                    ],
                    self::EMPLOYEE_DOCUMENTS_MANAGE => [
                        'label' => 'Manage Employee Documents',
                        'description' => 'Can upload, update, archive, and manage employee documents.',
                    ],

                    self::HR_REPORTS_VIEW => [
                        'label' => 'View HR Reports',
                        'description' => 'Can view HR reports, summaries, and workforce analytics.',
                    ],
                ],
            ],

            'Payroll' => [
                'label' => 'Payroll',
                'permissions' => [
                    self::PAYROLL_VIEW => [
                        'label' => 'View Payroll',
                        'description' => 'Can view payroll summaries and salary records.',
                    ],
                    self::PAYROLL_MANAGE => [
                        'label' => 'Manage Payroll',
                        'description' => 'Can process salaries and generate payroll slips.',
                    ],
                ],
            ],

            'Accounting' => [
                'label' => 'Accounting',
                'permissions' => [
                    self::ACCOUNTING_VIEW => [
                        'label' => 'View Accounting',
                        'description' => 'Can view accounting records, accounts, and financial information.',
                    ],

                    self::ACCOUNTING_MANAGE => [
                        'label' => 'Manage Accounting',
                        'description' => 'Can create, edit, configure, and manage accounting records and settings.',
                    ],

                    self::ACCOUNTING_CUSTOMERS_MANAGE => [
                        'label' => 'Manage Customers',
                        'description' => 'Can create, edit, and toggle customer statuses.',
                    ],

                    self::ACCOUNTING_INVOICES_MANAGE => [
                        'label' => 'Manage Sales Invoices',
                        'description' => 'Can create, edit, issue, post, and void sales invoices.',
                    ],

                    self::ACCOUNTING_VENDORS_MANAGE => [
                        'label' => 'Manage Vendors',
                        'description' => 'Can create, edit, and toggle vendor statuses.',
                    ],

                    self::ACCOUNTING_BILLS_MANAGE => [
                        'label' => 'Manage Purchase Bills',
                        'description' => 'Can create, edit, issue, post, and void purchase bills.',
                    ],

                    self::ACCOUNTING_PAYMENTS_MANAGE => [
                        'label' => 'Manage Vendor Payments',
                        'description' => 'Can record payments, allocate to invoices or bills, and post payments.',
                    ],

                    self::ACCOUNTING_REPORTS_VIEW => [
                        'label' => 'View Accounting Reports',
                        'description' => 'Can view receivable/payable aging, statements, ledgers, and financial reports.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string>
     */
    public static function all(): array
    {
        $all = [];

        foreach (self::grouped() as $group) {
            foreach (array_keys($group['permissions']) as $permission) {
                $all[] = $permission;
            }
        }

        return $all;
    }

    public static function has(string $permission): bool
    {
        return in_array($permission, self::all(), true);
    }

    /**
     * Default permissions for system Admin role.
     *
     * @return array<string>
     */
    public static function adminDefault(): array
    {
        return self::all();
    }

    /**
     * Default permissions for Manager role.
     *
     * @return array<string>
     */
    public static function managerDefault(): array
    {
        return [
            self::DASHBOARD_VIEW,
            self::COMPANY_PROFILE_VIEW,

            self::BRANCHES_VIEW,
            self::DEPARTMENTS_VIEW,
            self::DESIGNATIONS_VIEW,

            self::MEMBERS_VIEW,

            self::STAFF_VIEW,
            self::STAFF_MANAGE,

            self::WORK_SCHEDULES_VIEW,
            self::WORK_SCHEDULES_MANAGE,

            self::SHIFTS_VIEW,
            self::SHIFTS_MANAGE,

            self::HOLIDAYS_VIEW,
            self::HOLIDAYS_MANAGE,

            self::ATTENDANCE_VIEW,
            self::ATTENDANCE_MANAGE,

            self::LEAVE_VIEW,
            self::LEAVE_MANAGE,

            self::OVERTIME_VIEW,
            self::OVERTIME_MANAGE,

            self::EMPLOYEE_DOCUMENTS_VIEW,
            self::EMPLOYEE_DOCUMENTS_MANAGE,

            self::HR_REPORTS_VIEW,
        ];
    }

    /**
     * Default permissions for Staff role.
     *
     * @return array<string>
     */
    public static function staffDefault(): array
    {
        return [
            self::DASHBOARD_VIEW,
            self::COMPANY_PROFILE_VIEW,

            self::STAFF_VIEW,

            self::WORK_SCHEDULES_VIEW,

            self::ATTENDANCE_VIEW,

            self::LEAVE_VIEW,

            self::OVERTIME_VIEW,

            self::EMPLOYEE_DOCUMENTS_VIEW,
        ];
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     permissions: array<int, array{name: string, label: string}>,
     *     sub_groups: array<int, array{
     *         key: string,
     *         label: string,
     *         permissions: array<int, array{name: string, label: string}>
     *     }>
     * }>
     */
    public static function structured(): array
    {
        return [
            [
                'key' => 'general',
                'label' => 'General',
                'permissions' => [
                    [
                        'name' => self::DASHBOARD_VIEW,
                        'label' => 'View Dashboard',
                    ],
                    [
                        'name' => self::COMPANY_PROFILE_VIEW,
                        'label' => 'View Company Profile',
                    ],
                    [
                        'name' => self::COMPANY_PROFILE_UPDATE,
                        'label' => 'Manage Company Profile',
                    ],
                ],
                'sub_groups' => [
                    [
                        'key' => 'dashboard',
                        'label' => 'Dashboard',
                        'permissions' => [
                            [
                                'name' => self::DASHBOARD_VIEW,
                                'label' => 'View Dashboard',
                            ],
                        ],
                    ],
                    [
                        'key' => 'company-profile',
                        'label' => 'Company Profile',
                        'permissions' => [
                            [
                                'name' => self::COMPANY_PROFILE_VIEW,
                                'label' => 'View Company Profile',
                            ],
                            [
                                'name' => self::COMPANY_PROFILE_UPDATE,
                                'label' => 'Manage Company Profile',
                            ],
                        ],
                    ],
                ],
            ],

            [
                'key' => 'organization',
                'label' => 'Organization',
                'permissions' => [
                    [
                        'name' => self::BRANCHES_VIEW,
                        'label' => 'View Branches',
                    ],
                    [
                        'name' => self::BRANCHES_MANAGE,
                        'label' => 'Manage Branches',
                    ],
                    [
                        'name' => self::DEPARTMENTS_VIEW,
                        'label' => 'View Departments',
                    ],
                    [
                        'name' => self::DEPARTMENTS_MANAGE,
                        'label' => 'Manage Departments',
                    ],
                    [
                        'name' => self::DESIGNATIONS_VIEW,
                        'label' => 'View Designations',
                    ],
                    [
                        'name' => self::DESIGNATIONS_MANAGE,
                        'label' => 'Manage Designations',
                    ],
                ],
                'sub_groups' => [
                    [
                        'key' => 'branches',
                        'label' => 'Branches',
                        'permissions' => [
                            [
                                'name' => self::BRANCHES_VIEW,
                                'label' => 'View Branches',
                            ],
                            [
                                'name' => self::BRANCHES_MANAGE,
                                'label' => 'Manage Branches',
                            ],
                        ],
                    ],
                    [
                        'key' => 'departments',
                        'label' => 'Departments',
                        'permissions' => [
                            [
                                'name' => self::DEPARTMENTS_VIEW,
                                'label' => 'View Departments',
                            ],
                            [
                                'name' => self::DEPARTMENTS_MANAGE,
                                'label' => 'Manage Departments',
                            ],
                        ],
                    ],
                    [
                        'key' => 'designations',
                        'label' => 'Designations',
                        'permissions' => [
                            [
                                'name' => self::DESIGNATIONS_VIEW,
                                'label' => 'View Designations',
                            ],
                            [
                                'name' => self::DESIGNATIONS_MANAGE,
                                'label' => 'Manage Designations',
                            ],
                        ],
                    ],
                ],
            ],

            [
                'key' => 'users-access',
                'label' => 'Users & Access',
                'permissions' => [
                    [
                        'name' => self::MEMBERS_VIEW,
                        'label' => 'View Members',
                    ],
                    [
                        'name' => self::MEMBERS_INVITE,
                        'label' => 'Invite Members',
                    ],
                    [
                        'name' => self::MEMBERS_MANAGE,
                        'label' => 'Manage Members',
                    ],
                    [
                        'name' => self::ROLES_VIEW,
                        'label' => 'View Roles',
                    ],
                    [
                        'name' => self::ROLES_MANAGE,
                        'label' => 'Manage Roles',
                    ],
                    [
                        'name' => self::INVITATIONS_VIEW,
                        'label' => 'View Invitations',
                    ],
                    [
                        'name' => self::INVITATIONS_MANAGE,
                        'label' => 'Manage Invitations',
                    ],
                ],
                'sub_groups' => [
                    [
                        'key' => 'members',
                        'label' => 'Members',
                        'permissions' => [
                            [
                                'name' => self::MEMBERS_VIEW,
                                'label' => 'View Members',
                            ],
                            [
                                'name' => self::MEMBERS_INVITE,
                                'label' => 'Invite Members',
                            ],
                            [
                                'name' => self::MEMBERS_MANAGE,
                                'label' => 'Manage Members',
                            ],
                        ],
                    ],
                    [
                        'key' => 'roles',
                        'label' => 'Roles & Permissions',
                        'permissions' => [
                            [
                                'name' => self::ROLES_VIEW,
                                'label' => 'View Roles',
                            ],
                            [
                                'name' => self::ROLES_MANAGE,
                                'label' => 'Manage Roles',
                            ],
                        ],
                    ],
                    [
                        'key' => 'invitations',
                        'label' => 'Invitations',
                        'permissions' => [
                            [
                                'name' => self::INVITATIONS_VIEW,
                                'label' => 'View Invitations',
                            ],
                            [
                                'name' => self::INVITATIONS_MANAGE,
                                'label' => 'Manage Invitations',
                            ],
                        ],
                    ],
                ],
            ],

            [
                'key' => 'hrm',
                'label' => 'HRM',
                'permissions' => [
                    [
                        'name' => self::STAFF_VIEW,
                        'label' => 'View Staff Directory',
                    ],
                    [
                        'name' => self::STAFF_MANAGE,
                        'label' => 'Manage Staff Directory',
                    ],
                    [
                        'name' => self::WORK_SCHEDULES_VIEW,
                        'label' => 'View Work Schedules',
                    ],
                    [
                        'name' => self::WORK_SCHEDULES_MANAGE,
                        'label' => 'Manage Work Schedules',
                    ],
                    [
                        'name' => self::SHIFTS_VIEW,
                        'label' => 'View Shifts',
                    ],
                    [
                        'name' => self::SHIFTS_MANAGE,
                        'label' => 'Manage Shifts',
                    ],
                    [
                        'name' => self::HOLIDAYS_VIEW,
                        'label' => 'View Holidays',
                    ],
                    [
                        'name' => self::HOLIDAYS_MANAGE,
                        'label' => 'Manage Holidays',
                    ],
                    [
                        'name' => self::ATTENDANCE_VIEW,
                        'label' => 'View Attendance',
                    ],
                    [
                        'name' => self::ATTENDANCE_MANAGE,
                        'label' => 'Manage Attendance',
                    ],
                    [
                        'name' => self::LEAVE_VIEW,
                        'label' => 'View Leave Requests',
                    ],
                    [
                        'name' => self::LEAVE_MANAGE,
                        'label' => 'Manage Leave Requests',
                    ],
                    [
                        'name' => self::OVERTIME_VIEW,
                        'label' => 'View Overtime',
                    ],
                    [
                        'name' => self::OVERTIME_MANAGE,
                        'label' => 'Manage Overtime',
                    ],
                    [
                        'name' => self::EMPLOYEE_DOCUMENTS_VIEW,
                        'label' => 'View Employee Documents',
                    ],
                    [
                        'name' => self::EMPLOYEE_DOCUMENTS_MANAGE,
                        'label' => 'Manage Employee Documents',
                    ],
                    [
                        'name' => self::HR_REPORTS_VIEW,
                        'label' => 'View HR Reports',
                    ],
                ],
                'sub_groups' => [
                    [
                        'key' => 'staff',
                        'label' => 'Staff Directory',
                        'permissions' => [
                            [
                                'name' => self::STAFF_VIEW,
                                'label' => 'View Staff Directory',
                            ],
                            [
                                'name' => self::STAFF_MANAGE,
                                'label' => 'Manage Staff Directory',
                            ],
                        ],
                    ],
                    [
                        'key' => 'work-schedules',
                        'label' => 'Work Schedules',
                        'permissions' => [
                            [
                                'name' => self::WORK_SCHEDULES_VIEW,
                                'label' => 'View Work Schedules',
                            ],
                            [
                                'name' => self::WORK_SCHEDULES_MANAGE,
                                'label' => 'Manage Work Schedules',
                            ],
                        ],
                    ],
                    [
                        'key' => 'shifts',
                        'label' => 'Shifts',
                        'permissions' => [
                            [
                                'name' => self::SHIFTS_VIEW,
                                'label' => 'View Shifts',
                            ],
                            [
                                'name' => self::SHIFTS_MANAGE,
                                'label' => 'Manage Shifts',
                            ],
                        ],
                    ],
                    [
                        'key' => 'holidays',
                        'label' => 'Holidays',
                        'permissions' => [
                            [
                                'name' => self::HOLIDAYS_VIEW,
                                'label' => 'View Holidays',
                            ],
                            [
                                'name' => self::HOLIDAYS_MANAGE,
                                'label' => 'Manage Holidays',
                            ],
                        ],
                    ],
                    [
                        'key' => 'attendance',
                        'label' => 'Attendance',
                        'permissions' => [
                            [
                                'name' => self::ATTENDANCE_VIEW,
                                'label' => 'View Attendance',
                            ],
                            [
                                'name' => self::ATTENDANCE_MANAGE,
                                'label' => 'Manage Attendance',
                            ],
                        ],
                    ],
                    [
                        'key' => 'leave',
                        'label' => 'Leave',
                        'permissions' => [
                            [
                                'name' => self::LEAVE_VIEW,
                                'label' => 'View Leave Requests',
                            ],
                            [
                                'name' => self::LEAVE_MANAGE,
                                'label' => 'Manage Leave Requests',
                            ],
                        ],
                    ],
                    [
                        'key' => 'overtime',
                        'label' => 'Overtime',
                        'permissions' => [
                            [
                                'name' => self::OVERTIME_VIEW,
                                'label' => 'View Overtime',
                            ],
                            [
                                'name' => self::OVERTIME_MANAGE,
                                'label' => 'Manage Overtime',
                            ],
                        ],
                    ],
                    [
                        'key' => 'employee-documents',
                        'label' => 'Employee Documents',
                        'permissions' => [
                            [
                                'name' => self::EMPLOYEE_DOCUMENTS_VIEW,
                                'label' => 'View Employee Documents',
                            ],
                            [
                                'name' => self::EMPLOYEE_DOCUMENTS_MANAGE,
                                'label' => 'Manage Employee Documents',
                            ],
                        ],
                    ],
                    [
                        'key' => 'reports',
                        'label' => 'HR Reports',
                        'permissions' => [
                            [
                                'name' => self::HR_REPORTS_VIEW,
                                'label' => 'View HR Reports',
                            ],
                        ],
                    ],
                ],
            ],

            [
                'key' => 'payroll',
                'label' => 'Payroll',
                'permissions' => [
                    [
                        'name' => self::PAYROLL_VIEW,
                        'label' => 'View Payroll',
                    ],
                    [
                        'name' => self::PAYROLL_MANAGE,
                        'label' => 'Manage Payroll',
                    ],
                ],
                'sub_groups' => [
                    [
                        'key' => 'payroll-records',
                        'label' => 'Payroll Management',
                        'permissions' => [
                            [
                                'name' => self::PAYROLL_VIEW,
                                'label' => 'View Payroll',
                            ],
                            [
                                'name' => self::PAYROLL_MANAGE,
                                'label' => 'Manage Payroll',
                            ],
                        ],
                    ],
                ],
            ],

            [
                'key' => 'accounting',
                'label' => 'Accounting',
                'permissions' => [
                    [
                        'name' => self::ACCOUNTING_VIEW,
                        'label' => 'View Accounting',
                    ],
                    [
                        'name' => self::ACCOUNTING_MANAGE,
                        'label' => 'Manage Accounting',
                    ],
                    [
                        'name' => self::ACCOUNTING_CUSTOMERS_MANAGE,
                        'label' => 'Manage Customers',
                    ],
                    [
                        'name' => self::ACCOUNTING_INVOICES_MANAGE,
                        'label' => 'Manage Sales Invoices',
                    ],
                    [
                        'name' => self::ACCOUNTING_VENDORS_MANAGE,
                        'label' => 'Manage Vendors',
                    ],
                    [
                        'name' => self::ACCOUNTING_BILLS_MANAGE,
                        'label' => 'Manage Purchase Bills',
                    ],
                    [
                        'name' => self::ACCOUNTING_PAYMENTS_MANAGE,
                        'label' => 'Manage Payments',
                    ],
                    [
                        'name' => self::ACCOUNTING_REPORTS_VIEW,
                        'label' => 'View Accounting Reports',
                    ],
                ],
                'sub_groups' => [
                    [
                        'key' => 'accounting-general',
                        'label' => 'General Accounting',
                        'permissions' => [
                            [
                                'name' => self::ACCOUNTING_VIEW,
                                'label' => 'View Accounting',
                            ],
                            [
                                'name' => self::ACCOUNTING_MANAGE,
                                'label' => 'Manage Accounting',
                            ],
                        ],
                    ],
                    [
                        'key' => 'accounting-receivables',
                        'label' => 'Accounts Receivable',
                        'permissions' => [
                            [
                                'name' => self::ACCOUNTING_CUSTOMERS_MANAGE,
                                'label' => 'Manage Customers',
                            ],
                            [
                                'name' => self::ACCOUNTING_INVOICES_MANAGE,
                                'label' => 'Manage Sales Invoices',
                            ],
                        ],
                    ],
                    [
                        'key' => 'accounting-payables',
                        'label' => 'Accounts Payable',
                        'permissions' => [
                            [
                                'name' => self::ACCOUNTING_VENDORS_MANAGE,
                                'label' => 'Manage Vendors',
                            ],
                            [
                                'name' => self::ACCOUNTING_BILLS_MANAGE,
                                'label' => 'Manage Purchase Bills',
                            ],
                            [
                                'name' => self::ACCOUNTING_PAYMENTS_MANAGE,
                                'label' => 'Manage Payments',
                            ],
                        ],
                    ],
                    [
                        'key' => 'accounting-reports',
                        'label' => 'Accounting Reports',
                        'permissions' => [
                            [
                                'name' => self::ACCOUNTING_REPORTS_VIEW,
                                'label' => 'View Accounting Reports',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
