<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SettingKey;
use App\Models\Setting;

/**
 * Akses pengaturan dari tabel `settings`. Nilai di-cache per instance (per request).
 * Bila kunci belum ada di tabel, nilai awal dari SettingKey dipakai.
 */
final class SettingRepository
{
    /** @var array<string, mixed>|null */
    private ?array $values = null;

    public function get(SettingKey $key): mixed
    {
        $this->values ??= Setting::query()->pluck('value', 'key')->all();

        return $this->values[$key->value] ?? $key->defaultValue();
    }

    public function int(SettingKey $key): int
    {
        return (int) $this->get($key);
    }

    public function float(SettingKey $key): float
    {
        return (float) $this->get($key);
    }

    public function bool(SettingKey $key): bool
    {
        return (bool) $this->get($key);
    }

    public function string(SettingKey $key): string
    {
        return (string) $this->get($key);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function array(SettingKey $key): array
    {
        $value = $this->get($key);

        return is_array($value) ? $value : (array) $key->defaultValue();
    }

    /**
     * Semua pengaturan (nilai tersimpan atau nilai awal).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $result = [];

        foreach (SettingKey::cases() as $key) {
            $result[$key->value] = $this->get($key);
        }

        return $result;
    }

    public function set(SettingKey $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key->value], ['value' => $value]);
        $this->values = null;
    }
}
