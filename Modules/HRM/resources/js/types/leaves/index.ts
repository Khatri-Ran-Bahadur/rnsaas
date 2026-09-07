export type LeaveTypeValue =
    | 'annual'
    | 'sick'
    | 'casual'
    | 'unpaid'
    | 'maternity'
    | 'paternity'
    | 'other';

export type LeaveStatusValue = 'pending' | 'approved' | 'rejected';

export interface LeaveStaff {
    public_id?: string;
    name?: string;
    employee_code?: string;
    department?: string;
    avatar?: string;
}

export interface LeaveItem {
    public_id: string;
    staff?: LeaveStaff;
    leave_type: {
        value: LeaveTypeValue;
        label: string;
    };
    start_date: string;
    end_date: string;
    total_days: number;
    reason: string | null;
    status: {
        value: LeaveStatusValue;
        label: string;
    };
    is_active: boolean;
    created_by?: number | null;
    updated_by?: number | null;
    approved_by?: number | null;
    approved_at?: string | null;
    rejected_by?: number | null;
    rejected_at?: string | null;
    rejection_reason?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface LeaveFilters {
    search?: string;
    tenant_staff_id?: number | string | null;
    leave_type?: string | null;
    status?: string | null;
    from_date?: string | null;
    to_date?: string | null;
    active?: boolean | null;
    per_page?: number;
}

export interface LeaveStats {
    pending: number;
    approved: number;
    rejected: number;
    total_days: number;
}

export interface PaginatedLeaves {
    data: LeaveItem[];
    current_page: number;
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

export interface LeaveIndexProps {
    leaves: PaginatedLeaves;
    filters?: LeaveFilters;
    stats?: LeaveStats;
    staffMembers?: Array<{
        id: number;
        public_id: string;
        name: string;
        employee_code: string;
    }>;
}

export interface LeaveExceptionItem {
    id: string;
    employee_name: string;
    employee_code: string;
    exception_type: string;
    details: string;
    severity: 'critical' | 'high' | 'medium' | 'low';
    status: 'open' | 'under_review' | 'resolved' | 'ignored';
    detected_at: string;
}
