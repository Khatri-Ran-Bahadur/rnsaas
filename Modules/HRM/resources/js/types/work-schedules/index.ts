export interface WorkScheduleDay {
    id?: number;
    day_of_week: number;
    day_name: string;
    is_working_day: boolean;
    start_time?: string | null;
    end_time?: string | null;
    break_minutes?: number;
}

export interface WorkScheduleItem {
    id?: number;
    public_id: string;
    name: string;
    description: string | null;
    is_default: boolean;
    is_active: boolean;
    timezone: string;
    effective_from: string | null;
    effective_to: string | null;
    days?: WorkScheduleDay[];
    created_at?: string;
    updated_at?: string;
}

export interface WorkScheduleFilters {
    search?: string | null;
    is_active?: boolean | string | null;
    per_page?: number;
}

export interface WorkScheduleStats {
    total: number;
    active: number;
    inactive: number;
}

export interface PaginatedWorkSchedules {
    data: WorkScheduleItem[];
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

export interface WorkScheduleIndexProps {
    workSchedules: PaginatedWorkSchedules;
    filters: WorkScheduleFilters;
    stats?: WorkScheduleStats;
    can?: {
        manage?: boolean;
    };
}
