# CLAUDE.md

Panduan untuk Claude Code saat bekerja di repository ini. Baca `docs/PRD.md` untuk kebutuhan produk lengkap sebelum mengerjakan fitur baru.

## Ringkasan Project

Sistem KPI untuk divisi IT Development. Mengelola project, bug & task (dengan difficulty dan estimasi), alur kerja Programmer → QA → Manager approval, notes, serta menghitung KPI programmer dan QA secara otomatis.

## Tech Stack

- **Backend**: Laravel 13, PHP 8.3+
- **Frontend**: Inertia.js + React + TypeScript, Tailwind CSS, shadcn/ui
- **Database**: PostgreSQL
- **Testing**: Pest
- **Kualitas kode**: Laravel Pint (PHP), ESLint + Prettier (TS), Larastan

## Perintah

```bash
composer run dev            # server + queue + vite + log
php artisan test            # seluruh test
php artisan test --filter=NamaTest
vendor/bin/pint             # format PHP
vendor/bin/phpstan analyse  # static analysis
npm run lint                # ESLint
npm run types               # cek TypeScript (tsc --noEmit)
npm run build               # build produksi
php artisan migrate:fresh --seed   # reset DB dengan data contoh
```

Sebelum menyatakan tugas selesai, jalankan: `vendor/bin/pint`, `php artisan test`, `npm run types`, `npm run lint`.

## Struktur Direktori

```
app/
  Actions/            # logika bisnis, satu kelas satu aksi (mis. TransitionItemStatus)
  Enums/              # ItemStatus, ItemType, Priority, Role, ProjectStatus
  Http/Controllers/   # tipis: validasi → panggil Action → return Inertia response
  Http/Requests/      # Form Request untuk semua validasi
  Models/
  Policies/           # otorisasi per model
  Services/Kpi/       # KpiCalculator, WorkingTimeCalculator
resources/js/
  pages/              # halaman Inertia, mengikuti struktur route (pages/projects/show.tsx)
  components/         # komponen reusable; components/ui/ untuk shadcn
  layouts/
  types/              # tipe TypeScript untuk props & model
  lib/
tests/
  Feature/
  Unit/
```

## Aturan Domain (PENTING)

1. **Status item hanya boleh diubah lewat `App\Actions\Items\TransitionItemStatus`.** Jangan pernah melakukan `$item->update(['status' => ...])` di tempat lain. Action ini:
   - memvalidasi transisi menggunakan `ItemStatus::canTransitionTo()`,
   - mengecek otorisasi peran,
   - mewajibkan `reason` untuk transisi yang membutuhkannya (qa_failed, rejected, on_hold, reopen, cancelled),
   - memperbarui counter (`qa_fail_count`, `reject_count`, `reopen_count`) dan timestamp (`started_at`, `approved_at`),
   - menulis `item_status_logs` dan note sistem,
   - semuanya dalam satu `DB::transaction()`.
2. Daftar transisi yang sah didefinisikan **di satu tempat**: enum `ItemStatus`. Lihat tabel transisi di PRD bagian 5.4.
3. `difficulty` dan `estimate_days` terkunci setelah item pernah `in_progress`; hanya Manager yang boleh mengubah, dengan alasan, dan perubahan dicatat.
4. Perhitungan KPI hanya ada di `app/Services/Kpi/`. Jangan menghitung KPI di controller, model, atau frontend.
5. Durasi kerja dihitung dari `item_status_logs` (total waktu di status `in_progress`) dengan `WorkingTimeCalculator` yang memperhitungkan jam kerja, hari kerja, dan tabel `holidays`.
6. Periode KPI yang sudah ditutup (`kpi_periods.closed_at`) bersifat read-only; tampilkan dari `kpi_snapshots`, jangan dihitung ulang.
7. Semua nilai yang dapat dikonfigurasi (poin per difficulty, bobot, target, toleransi) dibaca dari tabel `settings`, bukan di-hardcode.

## Konvensi Backend

- Controller tipis. Validasi di Form Request, otorisasi di Policy (`$this->authorize()` atau `Gate`), logika di Action/Service.
- Gunakan PHP enum (backed string) untuk semua kolom status/tipe dan cast di model.
- Gunakan `declare(strict_types=1);` dan type hint lengkap, termasuk return type.
- Hindari N+1: selalu eager load relasi yang dikirim ke frontend. Aktifkan `Model::preventLazyLoading()` di non-produksi.
- Kirim data ke Inertia lewat API Resource atau array eksplisit; jangan kirim model mentah (hindari bocornya kolom sensitif).
- Untuk data berat di dashboard/KPI, gunakan deferred props atau lazy props Inertia.
- Migration: gunakan tipe PostgreSQL yang sesuai (`jsonb` untuk settings/metrics, `decimal(5,1)` untuk estimasi). Tambahkan indeks sesuai PRD bagian 8.
- Zona waktu aplikasi `Asia/Jakarta`; simpan timestamp sebagai `timestampTz`.
- Teks yang tampil ke user dalam Bahasa Indonesia; nama kelas, method, variabel, dan kolom dalam bahasa Inggris.

## Konvensi Frontend

- Semua file React dalam TypeScript (`.tsx`), function component, tanpa `any`.
- Definisikan tipe props halaman di `resources/js/types/`; sinkronkan dengan data yang dikirim controller.
- Form menggunakan `useForm` / komponen `Form` dari Inertia; tampilkan error validasi per field.
- Gunakan komponen shadcn/ui yang sudah ada sebelum membuat komponen baru.
- Tombol aksi transisi status hanya ditampilkan jika diizinkan; daftar aksi yang diizinkan dikirim dari backend (`allowed_transitions`), bukan dihitung ulang di frontend.
- Format tanggal dengan locale `id-ID`.

## Testing

- Setiap Action dan Service wajib punya test.
- Wajib ada test untuk: setiap transisi status yang sah, transisi yang ditolak, otorisasi per role, kunci estimasi, dan setiap rumus KPI (termasuk kasus tepi: tidak ada item, item cancelled, item reopen, on_hold, hari libur).
- Test memakai PostgreSQL (database `kpi_test`), bukan SQLite, karena ada fitur khusus PostgreSQL (`jsonb`).
- Gunakan factory dan state (mis. `Item::factory()->inProgress()`); jangan insert manual.

## Yang Tidak Boleh Dilakukan

- Mengubah status item tanpa `TransitionItemStatus`.
- Menghapus atau mengedit baris `item_status_logs` (append-only).
- Menghitung ulang KPI periode yang sudah ditutup.
- Menambah package Composer/NPM baru tanpa menyebutkan alasannya terlebih dahulu.
- Mengubah migration yang sudah dijalankan di produksi; buat migration baru.