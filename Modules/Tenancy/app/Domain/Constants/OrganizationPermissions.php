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

    public const ATTENDANCE_VIEW = 'attendance.view';

    public const ATTENDANCE_MANAGE = 'attendance.manage';

    public const LEAVE_VIEW = 'leave.view';

    public const LEAVE_MANAGE = 'leave.manage';

    // Payroll
    public const PAYROLL_VIEW = 'payroll.view';

    public const PAYROLL_MANAGE = 'payroll.manage';

    /**
     * @return array<string, array{label: string, permissions: array<string, array{label: string, description: string}>}>
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
                    self::ATTENDANCE_VIEW => [
                        'label' => 'View Attendance',
                        'description' => 'Can view staff attendance logs and records.',
                    ],
                    self::ATTENDANCE_MANAGE => [
                        'label' => 'Manage Attendance',
                        'description' => 'Can record, edit, and approve attendance entries.',
                    ],
                    self::LEAVE_VIEW => [
                        'label' => 'View Leave Requests',
                        'description' => 'Can view submitted employee leave applications.',
                    ],
                    self::LEAVE_MANAGE => [
                        'label' => 'Manage Leave Requests',
                        'description' => 'Can approve or reject employee leave applications.',
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
        ];
    }

    /**
     * @return array<string>
     */
    public static function all(): array
    {
        $all = [];
        foreach (self::grouped() as $group) {
            foreach (array_keys($group['permissions']) as $perm) {
                $all[] = $perm;
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
            self::ATTENDANCE_VIEW,
            self::ATTENDANCE_MANAGE,
            self::LEAVE_VIEW,
            self::LEAVE_MANAGE,
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
            self::ATTENDANCE_VIEW,
            self::LEAVE_VIEW,
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, permissions: array<int, array{name: string, label: string}>, sub_groups: array<int, array{key: string, label: string, permissions: array<int, array{name: string, label: string}>}>}>
     */
    public static function structured(): array
    {
        return [
            [
                'key' => 'general',
                'label' => 'General',
                'permissions' => [
                    ['name' => self::DASHBOARD_VIEW, 'label' => 'View Dashboard'],
                    ['name' => self::COMPANY_PROFILE_VIEW, 'label' => 'View Company Profile'],
                    ['name' => self::COMPANY_PROFILE_UPDATE, 'label' => 'Manage Company Profile'],
                ],
                'sub_groups' => [
                    [
                        'key' => 'dashboard',
                        'label' => 'Dashboard',
                        'permissions' => [
                            ['name' => self::DASHBOARD_VIEW, 'label' => 'View Dashboard'],
                        ],
                    ],
                    [
                        'key' => 'company-profile',
                        'label' => 'Company Profile',
                        'permissions' => [
                            ['name' => self::COMPANY_PROFILE_VIEW, 'label' => 'View Company Profile'],
                            ['name' => self::COMPANY_PROFILE_UPDATE, 'label' => 'Manage Company Profile'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'organization',
                'label' => 'Organization',
                'permissions' => [
                    ['name' => self::BRANCHES_VIEW, 'label' => 'View Branches'],
                    ['name' => self::BRANCHES_MANAGE, 'label' => 'Manage Branches'],
                    ['name' => self::DEPARTMENTS_VIEW, 'label' => 'View Departments'],
                    ['name' => self::DEPARTMENTS_MANAGE, 'label' => 'Manage Departments'],
                    ['name' => self::DESIGNATIONS_VIEW, 'label' => 'View Designations'],
                    ['name' => self::DESIGNATIONS_MANAGE, 'label' => 'Manage Designations'],
                ],
                'sub_groups' => [
                    [
                        'key' => 'branches',
                        'label' => 'Branches',
                        'permissions' => [
                            ['name' => self::BRANCHES_VIEW, 'label' => 'View Branches'],
                            ['name' => self::BRANCHES_MANAGE, 'label' => 'Manage Branches'],
                        ],
                    ],
                    [
                        'key' => 'departments',
                        'label' => 'Departments',
                        'permissions' => [
                            ['name' => self::DEPARTMENTS_VIEW, 'label' => 'View Departments'],
                            ['name' => self::DEPARTMENTS_MANAGE, 'label' => 'Manage Departments'],
                        ],
                    ],
                    [
                        'key' => 'designations',
                        'label' => 'Designations',
                        'permissions' => [
                            ['name' => self::DESIGNATIONS_VIEW, 'label' => 'View Designations'],
                            ['name' => self::DESIGNATIONS_MANAGE, 'label' => 'Manage Designations'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'users-access',
                'label' => 'Users & Access',
                'permissions' => [
                    ['name' => self::MEMBERS_VIEW, 'label' => 'View Members'],
                    ['name' => self::MEMBERS_INVITE, 'label' => 'Invite Members'],
                    ['name' => self::MEMBERS_MANAGE, 'label' => 'Manage Members'],
                    ['name' => self::ROLES_VIEW, 'label' => 'View Roles'],
                    ['name' => self::ROLES_MANAGE, 'label' => 'Manage Roles'],
                    ['name' => self::INVITATIONS_VIEW, 'label' => 'View Invitations'],
                    ['name' => self::INVITATIONS_MANAGE, 'label' => 'Manage Invitations'],
                ],
                'sub_groups' => [
                    [
                        'key' => 'members',
                        'label' => 'Members',
                        'permissions' => [
                            ['name' => self::MEMBERS_VIEW, 'label' => 'View Members'],
                            ['name' => self::MEMBERS_INVITE, 'label' => 'Invite Members'],
                            ['name' => self::MEMBERS_MANAGE, 'label' => 'Manage Members'],
                        ],
                    ],
                    [
                        'key' => 'roles',
                        'label' => 'Roles & Permissions',
                        'permissions' => [
                            ['name' => self::ROLES_VIEW, 'label' => 'View Roles'],
                            ['name' => self::ROLES_MANAGE, 'label' => 'Manage Roles'],
                        ],
                    ],
                    [
                        'key' => 'invitations',
                        'label' => 'Invitations',
                        'permissions' => [
                            ['name' => self::INVITATIONS_VIEW, 'label' => 'View Invitations'],
                            ['name' => self::INVITATIONS_MANAGE, 'label' => 'Manage Invitations'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'hrm',
                'label' => 'HRM',
                'permissions' => [
                    ['name' => self::STAFF_VIEW, 'label' => 'View Staff Directory'],
                    ['name' => self::STAFF_MANAGE, 'label' => 'Manage Staff Directory'],
                    ['name' => self::ATTENDANCE_VIEW, 'label' => 'View Attendance'],
                    ['name' => self::ATTENDANCE_MANAGE, 'label' => 'Manage Attendance'],
                    ['name' => self::LEAVE_VIEW, 'label' => 'View Leave Requests'],
                    ['name' => self::LEAVE_MANAGE, 'label' => 'Manage Leave Requests'],
                ],
                'sub_groups' => [
                    [
                        'key' => 'staff',
                        'label' => 'Staff Directory',
                        'permissions' => [
                            ['name' => self::STAFF_VIEW, 'label' => 'View Staff Directory'],
                            ['name' => self::STAFF_MANAGE, 'label' => 'Manage Staff Directory'],
                        ],
                    ],
                    [
                        'key' => 'attendance',
                        'label' => 'Attendance',
                        'permissions' => [
                            ['name' => self::ATTENDANCE_VIEW, 'label' => 'View Attendance'],
                            ['name' => self::ATTENDANCE_MANAGE, 'label' => 'Manage Attendance'],
                        ],
                    ],
                    [
                        'key' => 'leave',
                        'label' => 'Leave Requests',
                        'permissions' => [
                            ['name' => self::LEAVE_VIEW, 'label' => 'View Leave Requests'],
                            ['name' => self::LEAVE_MANAGE, 'label' => 'Manage Leave Requests'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'payroll',
                'label' => 'Payroll',
                'permissions' => [
                    ['name' => self::PAYROLL_VIEW, 'label' => 'View Payroll'],
                    ['name' => self::PAYROLL_MANAGE, 'label' => 'Manage Payroll'],
                ],
                'sub_groups' => [
                    [
                        'key' => 'payroll-records',
                        'label' => 'Payroll Management',
                        'permissions' => [
                            ['name' => self::PAYROLL_VIEW, 'label' => 'View Payroll'],
                            ['name' => self::PAYROLL_MANAGE, 'label' => 'Manage Payroll'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
