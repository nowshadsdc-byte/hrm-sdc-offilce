export interface AttendanceSettings {
    working_hours_start: string;
    working_hours_end: string;
    weekend_days: string[];
    timezone: string;
    auto_backup_enabled: boolean;
    backup_frequency: 'daily' | 'weekly' | 'monthly' | null;
    backup_path: string;
    last_backup_at: string | null;
}

export interface Shift {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
}
