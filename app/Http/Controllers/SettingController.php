<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Settings\UpdateSettings;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Models\Holiday;
use App\Models\KpiPeriod;
use App\Services\SettingRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class SettingController extends Controller
{
    public function edit(SettingRepository $settings): Response
    {
        $this->authorize('manageSettings', KpiPeriod::class);

        return Inertia::render('settings/index', [
            'settings' => $settings->all(),
            'holidays' => Holiday::query()
                ->where('date', '>=', now()->subYear()->startOfYear())
                ->orderBy('date')
                ->get()
                ->map(fn (Holiday $holiday): array => [
                    'id' => $holiday->id,
                    'date' => $holiday->date->toDateString(),
                    'name' => $holiday->name,
                ])
                ->all(),
        ]);
    }

    public function update(UpdateSettingsRequest $request, UpdateSettings $update): RedirectResponse
    {
        $update->handle($request->user(), $request->values());

        Inertia::flash('success', 'Pengaturan disimpan.');

        return back();
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $this->authorize('manageSettings', KpiPeriod::class);

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', Rule::unique('holidays', 'date')],
            'name' => ['required', 'string', 'max:255'],
        ], attributes: ['date' => 'tanggal', 'name' => 'nama']);

        Holiday::query()->create($data);

        return back();
    }

    public function destroyHoliday(Holiday $holiday): RedirectResponse
    {
        $this->authorize('manageSettings', KpiPeriod::class);

        $holiday->delete();

        return back();
    }

    public function syncHolidays(Request $request): RedirectResponse
    {
        $this->authorize('manageSettings', KpiPeriod::class);

        try {
            $url = 'https://calendar.google.com/calendar/ical/en.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics';
            $content = file_get_contents($url);
            
            if ($content !== false) {
                if (preg_match_all('/BEGIN:VEVENT.*?DTSTART(?:;VALUE=DATE)?:(\d{8}).*?SUMMARY:(.*?)\r?\n/s', $content, $matches, PREG_SET_ORDER)) {
                    $count = 0;
                    foreach ($matches as $match) {
                        $dateStr = $match[1];
                        $name = trim($match[2]);
                        
                        // Parse date YYYYMMDD
                        $date = sprintf('%s-%s-%s', substr($dateStr, 0, 4), substr($dateStr, 4, 2), substr($dateStr, 6, 2));
                        
                        // Ignore holidays more than 1 year ago to avoid clutter
                        if (now()->subYear()->startOfYear()->format('Y-m-d') <= $date) {
                            $holiday = Holiday::firstOrNew(['date' => $date]);
                            if (!$holiday->exists) {
                                $holiday->name = $name;
                                $holiday->save();
                                $count++;
                            }
                        }
                    }
                    Inertia::flash('success', "Berhasil sinkronisasi {$count} hari libur baru dari kalender.");
                } else {
                    Inertia::flash('error', 'Gagal mem-parsing format kalender Google.');
                }
            } else {
                Inertia::flash('error', 'Gagal mengunduh data dari kalender Google.');
            }
        } catch (\Exception $e) {
            Inertia::flash('error', 'Gagal melakukan sinkronisasi kalender.');
        }

        return back();
    }
}
