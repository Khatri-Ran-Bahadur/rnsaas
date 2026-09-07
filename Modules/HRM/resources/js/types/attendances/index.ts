export type AttendanceStatusType =
    | 'present'
    | 'absent'
    | 'late'
    | 'half_day'
    | 'on_leave'
    | 'holiday'
    | 'week_off';

export type AttendanceSourceType =
    | 'manual'
    | 'web'
    | 'mobile'
    | 'biometric'
    | 'import';

export interface AttendanceStaff {
    public_id?: string;
    name?: string;
    employee_code?: string;
    department?: string;
    avatar?: string;
}

export interface AttendanceItem {
    public_id: string;
    staff?: AttendanceStaff;
    attendance_date: string;
    check_in: string | null;
    check_out: string | null;
    worked_minutes: number;
    worked_hours: number;
    late_minutes: number;
    early_leave_minutes: number;
    overtime_minutes: number;
    status: {
        value: AttendanceStatusType;
        label: string;
    };
    source: {
        value: AttendanceSourceType;
        label: string;
    };
    notes: string | null;
    is_active: boolean;
    created_by?: number | null;
    updated_by?: number | null;
    created_at?: string;
    updated_at?: string;
}

export interface AttendanceFilters {
    search?: string;
    tenant_staff_id?: number | string | null;
    status?: string | null;
    source?: string | null;
    date?: string | null;
    from_date?: string | null;
    to_date?: string | null;
    active?: boolean | null;
    per_page?: number;
}

export interface AttendanceStats {
    present: number;
    absent: number;
    late: number;
    half_day: number;
    on_leave: number;
    holiday: number;
    week_off: number;
    total_hours: number;
}

export interface PaginatedAttendances {
    data: AttendanceItem[];
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

export interface AttendanceIndexProps {
    attendances: PaginatedAttendances;
    filters?: AttendanceFilters;
    stats?: AttendanceStats;
    staffMembers?: Array<{
        id: number;
        public_id: string;
        name: string;
        employee_code: string;
    }>;
}

// Attendance Correction Request Types
export interface AttendanceCorrectionItem {
    id: string;
    employee_name: string;
    employee_code: string;
    department?: string;
    attendance_date: string;
    original_check_in: string | null;
    requested_check_in: string | null;
    original_check_out: string | null;
    requested_check_out: string | null;
    original_worked_hours: string;
    requested_worked_hours: string;
    reason: string;
    requested_by: string;
    status: 'pending' | 'approved' | 'rejected';
    created_at: string;
}

// Attendance Exception Types
export type ExceptionSeverity = 'critical' | 'high' | 'medium' | 'low';
export type ExceptionStatus = 'open' | 'under_review' | 'resolved' | 'ignored';

export interface AttendanceExceptionItem {
    id: string;
    employee_name: string;
    employee_code: string;
    date: string;
    exception_type: string;
    details: string;
    severity: ExceptionSeverity;
    status: ExceptionStatus;
    detected_at: string;
}

// Attendance Excel Import Types
export interface ExcelColumnMapping {
    excelColumn: string;
    systemField: string;
}

export interface ImportPreviewRow {
    rowNumber: number;
    employee_code: string;
    employee_name: string;
    attendance_date: string;
    check_in: string;
    check_out: string;
    status: string;
    source: string;
    warning?: string;
    error?: string;
}

export interface ImportValidationSummary {
    totalRows: number;
    validRows: number;
    warningRows: number;
    errorRows: number;
    duplicateRows: number;
    existingRecords: number;
    newRecords: number;
}

export interface ImportResultSummary {
    imported: number;
    updated: number;
    skipped: number;
    failed: number;
}
