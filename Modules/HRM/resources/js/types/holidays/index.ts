export interface HolidayItem {
    public_id: string;
    name: string;
    start_date: string;
    end_date?: string | null;
    type: string;
    type_label?: string;
    description?: string | null;
    is_recurring: boolean;
    is_active: boolean;
    is_multi_day?: boolean;
}

export interface HolidayFilters {
    search?: string | null;
    type?: string | null;
    active?: boolean | string | null;
    per_page?: number;
}

export interface HolidayStats {
    total: number;
    active: number;
    upcoming: number;
}

export interface PaginatedHolidays {
    data: HolidayItem[];
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

export interface HolidayCategoryOption {
    value: string;
    label: string;
}

export interface HolidayIndexProps {
    holidays: PaginatedHolidays;
    filters: HolidayFilters;
    types?: HolidayCategoryOption[];
    stats?: HolidayStats;
    can?: {
        manage?: boolean;
    };
}
