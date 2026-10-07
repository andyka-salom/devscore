<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Kunci pengaturan di tabel `settings` beserta nilai awalnya (dipakai seeder).
 */
enum SettingKey: string
{
    case DifficultyPoints = 'difficulty_points';
    case ReopenWindowDays = 'reopen_window_days';
    case SelfAssignEnabled = 'self_assign_enabled';
    case OnTimeTolerance = 'on_time_tolerance';
    case MonthlyTargetPoints = 'monthly_target_points';
    case ProgrammerWeights = 'programmer_weights';
    case WeightByPoints = 'weight_by_points';
    case QaMonthlyThroughputTarget = 'qa_monthly_throughput_target';
    case QaReviewTargetHours = 'qa_review_target_hours';
    case QaWeights = 'qa_weights';
    case WorkStart = 'work_start';
    case WorkEnd = 'work_end';
    case WorkStartSaturday = 'work_start_saturday';
    case WorkEndSaturday = 'work_end_saturday';
    case WorkDays = 'work_days';

    public function defaultValue(): mixed
    {
        return match ($this) {
            self::DifficultyPoints => ['1' => 1, '2' => 2, '3' => 3, '4' => 5, '5' => 8],
            self::ReopenWindowDays => 30,
            self::SelfAssignEnabled => true,
            self::OnTimeTolerance => 0.1,
            self::MonthlyTargetPoints => 20,
            self::ProgrammerWeights => ['productivity' => 0.4, 'timeliness' => 0.3, 'quality' => 0.3],
            self::WeightByPoints => false,
            self::QaMonthlyThroughputTarget => 40,
            self::QaReviewTargetHours => 8,
            self::QaWeights => ['throughput' => 0.3, 'speed' => 0.3, 'accuracy' => 0.4],
            self::WorkStart => '08:00',
            self::WorkEnd => '17:00',
            self::WorkStartSaturday => '08:30',
            self::WorkEndSaturday => '13:00',
            // ISO-8601: 1 = Senin ... 7 = Minggu
            self::WorkDays => [1, 2, 3, 4, 5],
        };
    }
}
