import type {
    PaginatedWorkSchedules,
    WorkSchedule,
    WorkScheduleFilters,
} from './work-schedule'

export interface WorkScheduleIndexProps {
    workSchedules: PaginatedWorkSchedules
    filters: WorkScheduleFilters
}

export interface WorkScheduleShowProps {
    workSchedule: WorkSchedule
}