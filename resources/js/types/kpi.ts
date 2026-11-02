import type { EnumOption, Role } from '@/types';
import type { QaFinding } from '@/types/item';

export type KpiGrade = 'excellent' | 'good' | 'fair' | 'needs_improvement';

export interface DifficultyCount {
    difficulty: number;
    bug: number;
    task: number;
}

/** Field opsional tidak ada pada snapshot periode lama (sebelum rumus KPI Programmer baru). */
export interface ProgrammerMetrics {
    /** Jumlah & poin task yang diterima (done) pertama kali pada rentang. */
    item_count: number;
    points: number;
    by_difficulty?: DifficultyCount[];
    working_days?: number;
    target_points: number;
    /** Realisasi produktivitas tanpa batas 100%. */
    productivity_actual?: number | null;
    productivity: number | null;
    due_count?: number;
    due_points?: number;
    on_time_points?: number;
    pending_points?: number;
    timeliness: number | null;
    tested_count?: number;
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

export type TimelinessStatus = 'on_time' | 'late' | 'pending';

export interface ProgrammerKpiItem {
    id: number;
    code: string;
    title: string;
    type?: 'bug' | 'task';
    difficulty?: number | null;
    points: number;
    estimate_days: number;
    actual_days: number;
    due_date?: string | null;
    /** Submit versi yang akhirnya lolos QA. */
    submitted_at?: string | null;
    approved_at: string | null;
    /** Dihitung di produktivitas (diterima pertama kali pada rentang). */
    accepted?: boolean;
    /** null: deadline di luar rentang. */
    timeliness?: TimelinessStatus | null;
    first_tested_at?: string | null;
    qa_finding?: QaFinding | null;
    /** null: testing pertama di luar rentang. */
    quality_score?: number | null;
    /** Snapshot lama. */
    on_time?: boolean;
    clean?: boolean;
    qa_fail_count: number;
    reject_count: number;
    reopen_count: number;
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
    /** Ada komponen N/A atau testing pending; tidak ada pada snapshot lama. */
    provisional?: boolean;
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
