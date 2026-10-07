<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\SettingKey;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSettingsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $sumsToOne = static function (string $attribute, mixed $value, Closure $fail): void {
            if (is_array($value) && abs(array_sum(array_map('floatval', $value)) - 1) > 0.001) {
                $fail('Total bobot harus 100%.');
            }
        };

        return [
            'difficulty_points' => ['required', 'array:1,2,3,4,5'],
            'difficulty_points.*' => ['required', 'integer', 'min:0', 'max:100'],
            'reopen_window_days' => ['required', 'integer', 'min:0', 'max:365'],
            'self_assign_enabled' => ['required', 'boolean'],
            'on_time_tolerance' => ['required', 'numeric', 'min:0', 'max:1'],
            'monthly_target_points' => ['required', 'numeric', 'min:1', 'max:1000'],
            'programmer_weights' => ['required', 'array:productivity,timeliness,quality', $sumsToOne],
            'programmer_weights.*' => ['required', 'numeric', 'min:0', 'max:1'],
            'weight_by_points' => ['required', 'boolean'],
            'qa_monthly_throughput_target' => ['required', 'numeric', 'min:1', 'max:10000'],
            'qa_review_target_hours' => ['required', 'numeric', 'min:0.5', 'max:200'],
            'qa_weights' => ['required', 'array:throughput,speed,accuracy', $sumsToOne],
            'qa_weights.*' => ['required', 'numeric', 'min:0', 'max:1'],
            'work_start' => ['required', 'date_format:H:i'],
            'work_end' => ['required', 'date_format:H:i', 'after:work_start'],
            'work_start_saturday' => ['required', 'date_format:H:i'],
            'work_end_saturday' => ['required', 'date_format:H:i', 'after:work_start_saturday'],
            'work_days' => ['required', 'array', 'min:1'],
            'work_days.*' => ['integer', 'between:1,7', 'distinct'],
        ];
    }

    /**
     * Nilai siap simpan, dengan tipe yang konsisten.
     *
     * @return array<string, mixed>
     */
    public function values(): array
    {
        $data = $this->validated();

        return [
            SettingKey::DifficultyPoints->value => array_map('intval', $data['difficulty_points']),
            SettingKey::ReopenWindowDays->value => (int) $data['reopen_window_days'],
            SettingKey::SelfAssignEnabled->value => (bool) $data['self_assign_enabled'],
            SettingKey::OnTimeTolerance->value => (float) $data['on_time_tolerance'],
            SettingKey::MonthlyTargetPoints->value => (float) $data['monthly_target_points'],
            SettingKey::ProgrammerWeights->value => array_map('floatval', $data['programmer_weights']),
            SettingKey::WeightByPoints->value => (bool) $data['weight_by_points'],
            SettingKey::QaMonthlyThroughputTarget->value => (float) $data['qa_monthly_throughput_target'],
            SettingKey::QaReviewTargetHours->value => (float) $data['qa_review_target_hours'],
            SettingKey::QaWeights->value => array_map('floatval', $data['qa_weights']),
            SettingKey::WorkStart->value => $data['work_start'],
            SettingKey::WorkEnd->value => $data['work_end'],
            SettingKey::WorkStartSaturday->value => $data['work_start_saturday'],
            SettingKey::WorkEndSaturday->value => $data['work_end_saturday'],
            SettingKey::WorkDays->value => array_values(array_map('intval', $data['work_days'])),
        ];
    }
}
