export interface ShiftItem {
    public_id: string;
    name: string;
    code: string;
    description: string | null;
    start_time: string;
    end_time: string;
    break_minutes: number;
    late_grace_minutes: number;
    early_leave_grace_minutes: number;
    is_overnight: boolean;
    is_active: boolean;
}

export interface ShiftFilters {
    search?: string | null;
    is_active?: boolean | string | null;
    per_page?: number;
}

export interface ShiftStats {
    total: number;
    active: number;
    night: number;
}

export interface PaginatedShifts {
    data: ShiftItem[];
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

export interface ShiftIndexProps {
    shifts: PaginatedShifts;
    filters: ShiftFilters;
    stats?: ShiftStats;
    can?: {
        manage?: boolean;
    };
}
