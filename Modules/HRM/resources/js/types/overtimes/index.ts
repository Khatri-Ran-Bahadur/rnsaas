export interface OvertimeStaff {
    id?: number;
    public_id: string;
    name: string;
    employee_code: string;
    department?: string | null;
    designation?: string | null;
}

export interface OvertimeItem {
    public_id: string;
    tenant_staff_id?: number;
    staff?: OvertimeStaff | null;
    date: string;
    start_time: string;
    end_time: string;
    total_minutes: number;
    total_hours: number | string;
    type: 'regular' | 'weekend' | 'holiday' | string;
    type_label: string;
    rate_multiplier: number | string;
    reason: string | null;
    status: 'pending' | 'approved' | 'rejected' | string;
    status_label: string;
    rejection_reason?: string | null;
    is_active: boolean;
    approved_at?: string | null;
    rejected_at?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface OvertimeFilters {
    search?: string | null;
    tenant_staff_id?: number | string | null;
    type?: string | null;
    status?: string | null;
    from_date?: string | null;
    to_date?: string | null;
    per_page?: number;
}

export interface OvertimeStats {
    total: number;
    pending: number;
    approved: number;
    total_minutes: number;
}

export interface PaginatedOvertimes {
    data: OvertimeItem[];
    links?: Array<{ url: string | null; label: string; active: boolean }>;
    meta?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
}

export interface OvertimeOption {
    value: string;
    label: string;
}

export interface OvertimeIndexProps {
    overtimes: PaginatedOvertimes;
    filters: OvertimeFilters;
    staff_members: Array<{ id: number; public_id: string; name: string; employee_code: string }>;
    types: OvertimeOption[];
    statuses: OvertimeOption[];
    stats: OvertimeStats;
    can?: {
        manage?: boolean;
    };
}
