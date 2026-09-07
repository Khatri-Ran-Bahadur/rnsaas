export interface HRMOverviewStats {
    totalEmployees: number;
    presentToday: number;
    absentToday: number;
    onLeaveToday: number;
    lateToday: number;
    pendingLeaves: number;
    pendingCorrections: number;
    overtimeHoursToday: number;
}

export interface AttendanceBreakdownItem {
    label: string;
    count: number;
    percentage: number;
    color: string;
}

export interface RecentHRActivityItem {
    id: string;
    type: 'attendance_corrected' | 'leave_approved' | 'leave_rejected' | 'overtime_approved' | 'status_changed' | 'import_completed';
    title: string;
    description: string;
    actor: string;
    timestamp: string;
}
