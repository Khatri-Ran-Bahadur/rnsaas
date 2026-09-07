export interface AttendanceReportSummary {
    present: number;
    absent: number;
    late: number;
    halfDay: number;
    leave: number;
    workedHours: number;
    overtimeHours: number;
}

export interface LeaveReportSummary {
    totalRequests: number;
    approved: number;
    rejected: number;
    pending: number;
    totalDays: number;
}

export interface OvertimeReportSummary {
    totalRequests: number;
    approved: number;
    pending: number;
    totalHours: number;
}

export interface EmployeeReportSummary {
    totalEmployees: number;
    active: number;
    suspended: number;
    terminated: number;
}
