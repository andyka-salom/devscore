import type { EnumOption, Role } from '@/types';

export type KpiGrade = 'excellent' | 'good' | 'fair' | 'needs_improvement';

export interface ProgrammerMetrics {
    item_count: number;
    points: number;
    target_points: number;
    productivity: number | null;
    timeliness: number | null;
    quality: number | null;
}

export interface QaMetrics {
    decision_count: number;
    pass_count: number;
    fail_count: number;
    throughput_target: number;
    avg_review_hours: number | null;
    throughput: number | null;
    speed: number | null;
    accuracy: number | null;
}

export interface ProgrammerKpiItem {
    id: number;
    code: string;
    title: string;
    points: number;
    estimate_days: number;
    actual_days: number;
    on_time: boolean;
    clean: boolean;
    qa_fail_count: number;
    reject_count: number;
    reopen_count: number;
    approved_at: string | null;
}

export interface QaKpiItem {
    id: number;
    code: string | null;
    title: string | null;
    decision: 'qa_passed' | 'qa_failed';
    review_hours: number;
    overturned: boolean;
    decided_at: string | null;
}

interface KpiRowBase {
    user_id: number;
    name: string;
    final_score: number | null;
    grade: EnumOption<KpiGrade> | null;
    note: string | null;
}

/** App\Services\Kpi\KpiResult::toArray() */
export type KpiRow =
    | (KpiRowBase & {
          role: EnumOption<Extract<Role, 'programmer'>>;
          metrics: ProgrammerMetrics;
          items: ProgrammerKpiItem[];
      })
    | (KpiRowBase & { role: EnumOption<Extract<Role, 'qa'>>; metrics: QaMetrics; items: QaKpiItem[] });

export interface KpiFilters {
    start: string;
    end: string;
}

export interface KpiSource {
    type: 'live' | 'snapshot';
    label: string;
    closed_at: string | null;
}

export interface KpiPeriodInfo {
    year: number;
    month: number;
    label: string;
    closed: boolean;
    can_close: boolean;
}

export interface KpiIndexPageProps {
    filters: KpiFilters;
    scope: 'team' | 'self';
    source: KpiSource;
    period: KpiPeriodInfo | null;
    rows?: KpiRow[];
}

export interface KpiShowPageProps {
    filters: KpiFilters;
    source: KpiSource;
    row?: KpiRow | null;
}
