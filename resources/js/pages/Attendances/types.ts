export type EmployeeOption = {
    id: number;
    user_id?: number | null;
    user?: {
        id: number;
        name: string;
    } | null;
};

export type DeviceOption = {
    id: number;
    name: string;
};

export type AttendanceRecord = {
    id: number;
    employee_id: number;
    user_id: number;
    device_id: number | null;
    date: string;
    check_in: string;
    check_out: string | null;
    employee?: {
        id: number;
        user?: {
            name: string;
        } | null;
    } | null;
    device?: {
        id: number;
        name: string;
    } | null;
};

export type PaginatedAttendances = {
    data: AttendanceRecord[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};
