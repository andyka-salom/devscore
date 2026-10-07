# PRD — Sistem KPI Divisi IT Development

| | |
|---|---|
| Versi | 1.0 (Draft) |
| Tanggal | 7 Oktober 2026 |
| Pemilik | Manager IT Development |
| Stack | Laravel 13, Inertia.js, React (TypeScript), PostgreSQL |

---

## 1. Latar Belakang

Saat ini progres pekerjaan tim development (bug fixing & pengembangan fitur) belum tercatat secara terstruktur, sehingga penilaian kinerja programmer dan QA cenderung subjektif. Dibutuhkan sistem yang mencatat setiap bug/task per project lengkap dengan estimasi, tingkat kesulitan, alur QA, dan approval manager, lalu menghitung KPI secara otomatis dan transparan dari data tersebut.

## 2. Tujuan

1. Menyediakan satu tempat untuk mengelola project beserta seluruh bug dan task-nya.
2. Menegakkan alur kerja yang konsisten: Assign → Kerjakan → QA → Approval Manager.
3. Mencatat seluruh perubahan status sebagai jejak audit (audit trail).
4. Menghitung KPI programmer dan QA secara otomatis, objektif, dan dapat ditelusuri asal angkanya.
5. Memfasilitasi komunikasi melalui notes di setiap item.

## 3. Di Luar Lingkup (MVP)

- Integrasi Git (GitHub/GitLab), CI/CD, atau time tracker eksternal.
- Penggajian/bonus otomatis berdasarkan KPI.
- Aplikasi mobile native (cukup web responsif).
- Multi-divisi / multi-tenant.
- Sprint/scrum board penuh (velocity, burndown).

## 4. Peran & Hak Akses

| Aksi | Programmer | QA | Manager | Admin |
|---|:-:|:-:|:-:|:-:|
| Kelola user & role | | | | ✓ |
| Kelola pengaturan KPI | | | ✓ | ✓ |
| Buat / edit project | | | ✓ | |
| Lihat project (sebagai anggota) | ✓ | ✓ | ✓ | ✓ |
| Buat item (bug/task) di Backlog | ✓ | ✓ | ✓ | |
| Set difficulty & estimasi (triage) | | | ✓ | |
| Assign programmer & QA | | | ✓ | |
| Self-assign item yang sudah di-triage | ✓ (jika diaktifkan) | | | |
| Mulai / submit ke QA | ✓ (assignee) | | | |
| Pass / Fail QA | | ✓ (QA item) | | |
| Approve / Reject | | | ✓ | |
| Reopen item Done | | | ✓ | |
| Tambah notes | ✓ | ✓ | ✓ | |
| Lihat KPI diri sendiri | ✓ | ✓ | ✓ | |
| Lihat KPI seluruh tim | | | ✓ | ✓ |

Catatan: satu user hanya memiliki satu role. Admin bisa merangkap Manager jika diperlukan.

## 5. Fitur

### 5.1 Manajemen Pengguna
- CRUD user oleh Admin: nama, email, role, status aktif/nonaktif.
- User nonaktif tidak bisa login, tetapi data historis & KPI-nya tetap tersimpan.
- Login dengan email & password, lupa password via email.

### 5.2 Project
Field:
- Nama, kode project (mis. `ERP`, unik, dipakai sebagai prefix item: `ERP-42`).
- Deskripsi (rich text / markdown).
- Status: `planning`, `active`, `on_hold`, `completed`, `archived`.
- Tanggal mulai & target selesai.
- **Links**: daftar link berlabel (repository, Figma, dokumen requirement, staging, production, dll).
- **Timeline/Milestone**: judul, deskripsi, tanggal target, status (selesai/belum).
- **Anggota project**: programmer & QA yang terlibat.
- Ringkasan otomatis: jumlah item per status, % progres (poin Done / total poin).

### 5.3 Item (Bug & Task)
Field:
- Tipe: `bug` atau `task`.
- Kode otomatis per project (`ERP-42`).
- Judul, deskripsi (markdown), langkah reproduksi (khusus bug).
- Prioritas: `low`, `medium`, `high`, `critical`.
- Tingkat kesulitan (difficulty): 1–5.
- Estimasi: dalam hari kerja (desimal, kelipatan 0.5).
- Assignee (programmer), QA penanggung jawab.
- Due date (opsional).
- Milestone terkait (opsional).
- Lampiran (screenshot/file) — opsional untuk MVP.
- Field turunan (dihitung sistem): jumlah QA fail, jumlah reject manager, jumlah reopen, durasi kerja aktual.

Fitur list:
- Tampilan tabel dengan filter: project, tipe, status, assignee, QA, prioritas, difficulty.
- Tampilan Kanban per status (drag & drop hanya untuk transisi yang diizinkan).
- Pencarian berdasarkan kode & judul.

### 5.4 Workflow Status

```
backlog ──(triage + assign)──► assigned ──► in_progress ──► ready_for_qa
                                               ▲   │              │
                                  on_hold ◄────┘   │       ┌──────┴──────┐
                                                   │       ▼             ▼
                                                   │   qa_failed     qa_passed
                                                   │       │          │      │
                                                   └───────┘      rejected  done
                                                   ▲                  │      │
                                                   └──────────────────┘      │
                                                   ▲   (reopen)              │
                                                   └─────────────────────────┘
cancelled: dapat dicapai dari status mana pun selain done (oleh Manager)
```

| Dari | Ke | Oleh | Syarat |
|---|---|---|---|
| backlog | assigned | Manager (atau programmer jika self-assign aktif) | difficulty, estimasi, assignee, QA terisi |
| assigned | in_progress | Assignee | — |
| in_progress | on_hold | Assignee / Manager | alasan wajib diisi |
| on_hold | in_progress | Assignee / Manager | — |
| in_progress | ready_for_qa | Assignee | catatan perubahan opsional |
| ready_for_qa | qa_passed | QA item | — |
| ready_for_qa | qa_failed | QA item | catatan alasan wajib |
| qa_failed | in_progress | Assignee | — |
| qa_passed | done | Manager | (approve) |
| qa_passed | rejected | Manager | catatan alasan wajib |
| rejected | in_progress | Assignee | — |
| done | in_progress | Manager | (reopen) alasan wajib, maks. 30 hari sejak done |
| selain done | cancelled | Manager | alasan wajib |

Setiap transisi **wajib** tercatat di log status (siapa, kapan, dari, ke, catatan).

### 5.5 Notes
- Programmer, QA, dan Manager dapat menambahkan notes di setiap item dan setiap project.
- Notes menampilkan nama, role (badge), dan waktu.
- Mendukung markdown sederhana dan mention `@nama` (memicu notifikasi).
- Notes bisa diedit/dihapus oleh penulisnya dalam 15 menit; setelah itu terkunci.
- Catatan wajib dari transisi (alasan QA fail, reject, dsb.) otomatis muncul juga sebagai note bertanda khusus.
- Halaman detail item menampilkan **timeline gabungan**: notes + perubahan status, urut kronologis.

### 5.6 KPI
Lihat bagian 7 untuk rumus. Fitur:
- KPI dihitung per periode bulanan.
- Manager dapat **menutup periode** (close period); setelah ditutup, hasil KPI disimpan sebagai snapshot dan tidak berubah meskipun data item berubah.
- Setiap angka KPI dapat di-drill down ke daftar item yang membentuknya.

### 5.7 Dashboard & Laporan
- **Dashboard Programmer**: item aktif saya, item yang gagal QA/di-reject, skor KPI bulan berjalan.
- **Dashboard QA**: antrian `ready_for_qa`, rata-rata waktu review, skor KPI.
- **Dashboard Manager**: antrian `qa_passed` menunggu approval, item backlog belum di-triage, ringkasan per project, tabel KPI tim, grafik tren KPI 6 bulan.
- Ekspor laporan KPI per periode ke Excel/CSV dan PDF.

### 5.8 Notifikasi (in-app + email opsional)
- Item di-assign ke saya.
- Item saya gagal QA / di-reject / di-reopen.
- Ada item baru di antrian QA saya.
- Ada item menunggu approval (Manager).
- Saya di-mention di notes.

### 5.9 Pengaturan (Manager/Admin)
- Pemetaan difficulty → poin.
- Bobot komponen KPI dan target poin bulanan per programmer.
- Toleransi ketepatan waktu (default 10%).
- Jam kerja harian, hari kerja, dan daftar hari libur nasional/cuti bersama.
- Aktif/nonaktif self-assign.

## 6. Aturan Bisnis

1. **Triage dulu**: item tidak bisa keluar dari `backlog` sebelum difficulty & estimasi diisi Manager.
2. **Kunci estimasi**: setelah item pertama kali masuk `in_progress`, difficulty dan estimasi terkunci. Hanya Manager yang dapat mengubah, dengan alasan wajib, dan perubahan dicatat di log.
3. Item `cancelled` tidak dihitung dalam KPI siapa pun.
4. Item dihitung ke periode KPI berdasarkan tanggal **masuk `done`** (approved_at).
5. Waktu dalam status `on_hold`, `ready_for_qa`, `qa_passed`, dan menunggu lainnya **tidak** dihitung sebagai durasi kerja programmer.
6. Item yang di-reopen kembali dihitung sebagai kegagalan kualitas pada periode item tersebut selesai pertama kali (jika periode belum ditutup) atau periode berjalan (jika sudah ditutup).
7. Programmer tidak bisa melakukan transisi pada item yang bukan miliknya; QA hanya pada item di mana ia ditunjuk sebagai QA.
8. Semua transisi status dijalankan dalam transaksi database bersama pencatatan log.

## 7. Perhitungan KPI

### 7.1 Poin item (default, dapat diubah)

| Difficulty | Poin |
|---|---|
| 1 – Sangat mudah | 1 |
| 2 – Mudah | 2 |
| 3 – Sedang | 3 |
| 4 – Sulit | 5 |
| 5 – Sangat sulit | 8 |

### 7.2 Durasi kerja aktual
`actual_days` = total jam kerja selama item berstatus `in_progress` (dijumlahkan dari seluruh siklus, berdasarkan log status) ÷ jam kerja per hari. Hanya menghitung jam kerja pada hari kerja, di luar hari libur.

### 7.3 KPI Programmer (per periode)
Himpunan **D** = item milik programmer yang masuk `done` pada periode tersebut.

- **Produktivitas (P)** = min(Σ poin D ÷ target poin bulanan, 1.2) × 100
  (dibatasi 120% agar tidak mendominasi skor)
- **Ketepatan Waktu (T)** = jumlah item D dengan `actual_days ≤ estimate_days × (1 + toleransi)` ÷ |D| × 100
- **Kualitas (Q)** = jumlah item D dengan `qa_fail_count = 0` dan `reject_count = 0` dan tidak di-reopen ÷ |D| × 100
- **Skor Akhir** = w₁·P + w₂·T + w₃·Q (default w₁ = 40%, w₂ = 30%, w₃ = 30%)

Jika |D| = 0, T dan Q ditampilkan "—" dan skor akhir hanya ditampilkan bila Manager mengisi keterangan (mis. cuti panjang).

Ketepatan waktu dan kualitas dapat dibobot dengan poin item (opsi pengaturan), agar item sulit lebih berpengaruh.

### 7.4 KPI QA (per periode)
- **Throughput** = jumlah keputusan QA (pass/fail) pada periode.
- **Kecepatan Review** = rata-rata jam kerja dari `ready_for_qa` hingga keputusan QA; target default ≤ 8 jam kerja.
- **Akurasi QA** = 1 − (item yang di-pass QA lalu di-reject Manager atau di-reopen ÷ total item yang di-pass) × 100.
- **Skor Akhir** = bobot dapat diatur (default 30% throughput vs target, 30% kecepatan, 40% akurasi).

### 7.5 Penilaian (default)
| Skor | Predikat |
|---|---|
| ≥ 90 | Sangat Baik |
| 75 – 89 | Baik |
| 60 – 74 | Cukup |
| < 60 | Perlu Perbaikan |

## 8. Model Data (ringkas)

```
users                (id, name, email, password, role, is_active, timestamps)
projects             (id, code, name, description, status, start_date, end_date, created_by, timestamps)
project_members      (project_id, user_id)
project_links        (id, project_id, label, url, sort_order)
project_milestones   (id, project_id, title, description, due_date, completed_at)
items                (id, project_id, number, type, title, description, steps_to_reproduce,
                      priority, difficulty, estimate_days, status, assignee_id, qa_id,
                      milestone_id, due_date, created_by,
                      qa_fail_count, reject_count, reopen_count,
                      started_at, approved_at, timestamps, soft deletes)
item_status_logs     (id, item_id, from_status, to_status, user_id, reason, created_at)
notes                (id, notable_type, notable_id, user_id, body, is_system, timestamps)
attachments          (id, attachable_type, attachable_id, path, original_name, size, uploaded_by)
holidays             (id, date, name)
settings             (key, value jsonb)
kpi_periods          (id, year, month, closed_at, closed_by)
kpi_snapshots        (id, kpi_period_id, user_id, role, metrics jsonb, final_score, note)
```

Indeks penting: `items(project_id, status)`, `items(assignee_id, status)`, `items(qa_id, status)`, `items(approved_at)`, `item_status_logs(item_id, created_at)`. Kode item unik per `(project_id, number)`.

## 9. Daftar Halaman

1. Login / Lupa password
2. Dashboard (berbeda per role)
3. Project — daftar, detail (tab: Ringkasan, Item, Timeline, Links, Notes, Anggota), form
4. Item — tabel, Kanban, detail (info + timeline notes & status), form
5. Antrian QA
6. Antrian Approval
7. KPI — saya, tim (Manager), detail per user dengan drill-down
8. Laporan & ekspor
9. Pengaturan — KPI, kalender kerja & libur
10. Manajemen user (Admin)

## 10. Kebutuhan Non-Fungsional

- Waktu respons halaman < 1 detik untuk data ≤ 10.000 item.
- Web responsif (desktop utama, tablet & mobile dapat dipakai).
- Bahasa antarmuka: Indonesia. Zona waktu: Asia/Jakarta.
- Keamanan: otorisasi berbasis Policy di server untuk setiap aksi; tidak mengandalkan UI.
- Seluruh perubahan status dan perubahan estimasi/difficulty ter-audit.
- Backup database harian.

## 11. Rencana Rilis

| Fase | Isi |
|---|---|
| **MVP (Fase 1)** | Auth, user & role, project (deskripsi, links, milestone, anggota), item CRUD, workflow status lengkap + log, notes, antrian QA & approval, dashboard dasar |
| **Fase 2** | Perhitungan KPI, halaman KPI, pengaturan KPI, kalender kerja & hari libur, close period & snapshot |
| **Fase 3** | Kanban drag & drop, notifikasi & mention, lampiran, ekspor Excel/PDF, grafik tren |

## 12. Pertanyaan Terbuka

1. Apakah programmer boleh self-assign, atau semua assignment oleh Manager?
2. Apakah satu item bisa dikerjakan lebih dari satu programmer? (MVP: tidak)
3. Berapa target poin bulanan awal per programmer? Sama untuk semua level (junior/senior)?
4. Apakah bug yang ditemukan di produksi perlu ditautkan ke item asalnya untuk penalti kualitas?
5. Apakah KPI Manager juga perlu diukur (mis. kecepatan triage & approval)?