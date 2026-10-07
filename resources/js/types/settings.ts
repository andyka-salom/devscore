export interface KpiSettings {
    difficulty_points: Record<'1' | '2' | '3' | '4' | '5', number>;
    reopen_window_days: number;
    self_assign_enabled: boolean;
    on_time_tolerance: number;
    monthly_target_points: number;
    programmer_weights: { productivity: number; timeliness: number; quality: number };
    weight_by_points: boolean;
    qa_monthly_throughput_target: number;
    qa_review_target_hours: number;
    qa_weights: { throughput: number; speed: number; accuracy: number };
    work_start: string;
    work_end: string;
    work_start_saturday: string;
    work_end_saturday: string;
    work_days: number[];
}

export interface Holiday {
    id: number;
    date: string;
    name: string;
}

export interface SettingsPageProps {
    settings: KpiSettings;
    holidays: Holiday[];
}
