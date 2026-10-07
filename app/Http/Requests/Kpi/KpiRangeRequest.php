<?php

declare(strict_types=1);

namespace App\Http\Requests\Kpi;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Filter rentang tanggal KPI. Default: bulan berjalan.
 */
final class KpiRangeRequest extends FormRequest
{
    private const int MAX_RANGE_DAYS = 366;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start' => ['nullable', 'date_format:Y-m-d'],
            'end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty() && $this->from()->diffInDays($this->to()) > self::MAX_RANGE_DAYS) {
                    $validator->errors()->add('end', 'Rentang maksimal 1 tahun.');
                }
            },
        ];
    }

    public function from(): CarbonImmutable
    {
        $start = $this->string('start')->toString();

        return $start !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d', $start)->startOfDay()
            : CarbonImmutable::now()->startOfMonth();
    }

    public function to(): CarbonImmutable
    {
        $end = $this->string('end')->toString();

        return $end !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d', $end)->startOfDay()
            : CarbonImmutable::now()->endOfMonth()->startOfDay();
    }

    /**
     * @return array{start: string, end: string}
     */
    public function filters(): array
    {
        return ['start' => $this->from()->toDateString(), 'end' => $this->to()->toDateString()];
    }
}
