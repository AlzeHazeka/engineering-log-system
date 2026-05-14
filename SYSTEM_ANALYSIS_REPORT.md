# System Management Workstation — System Analysis Report

Generated: 2026-05-02

## 🧩 A. Overview Sistem

**Tujuan sistem**
- Menjadi “workstation” internal untuk tracking pekerjaan engineering yang ringan: status sistem, pengelompokan pekerjaan (feature), dan timeline aktivitas (logs).

**Konsep utama**
- **System → Feature → Logs**
  - **System** = aplikasi/layanan (level tertinggi).
  - **Feature** = unit pekerjaan untuk pengelompokan progress/laporan (opsional tapi disarankan).
  - **Logs** = pusat data timeline (progress, bug, fix, deployment, maintenance, decision, idea).

**Cara penggunaan (ringkas)**
- Buat **System**, set **Status** (operasional) dan **Stage** (lifecycle) + optional **Released At**.
- Tambahkan **Features** di dalam System untuk mengelompokkan pekerjaan.
- Catat aktivitas lewat **Logs**:
  - Log global (tanpa feature) untuk deployment/maintenance umum.
  - Log per feature untuk progress/bug/fix.
  - Fix dapat mereferensikan **banyak bug** (many-to-many references).

---

## 🧱 B. Struktur Database

Sumber schema utama (migrations):
- `database/migrations/2026_05_02_000001_create_systems_table.php`
- `database/migrations/2026_05_02_000002_create_features_table.php`
- `database/migrations/2026_05_02_000003_create_logs_table.php`
- `database/migrations/2026_05_02_000004_create_log_references_table.php`
- `database/migrations/2026_05_02_000005_alter_logs_status_to_string.php`

### 1) `systems`

**Field penting**
- `id`
- `name`, `slug` (unique)
- `status` (enum): `active | maintenance | paused | deprecated`
- `stage` (string nullable): `planning | development | production | maintenance` atau null (fallback)
- `released_at` (date nullable)
- `description` (text nullable)
- `repository_url` (string nullable)
- timestamps

**Fungsi**
- Menyimpan metadata sistem + kondisi operasional (**status**) dan fase lifecycle (**stage**).

**Index**
- `status`

### 2) `features`

**Field penting**
- `id`
- `system_id` (FK → systems, cascade delete)
- `title`, `description` (nullable)
- `status` (enum): `planned | in_progress | done | on_hold`
- `progress` (0–100, default 0)
- `category` (enum): `feature | improvement | maintenance`
- `start_date`, `due_date`, `completed_at` (date nullable)
- `assigned_team` (string nullable)
- timestamps

**Fungsi**
- Pengelompokan pekerjaan per system; mempermudah ringkasan progress dan grouping logs.

**Index**
- (`system_id`, `status`)
- `due_date`

### 3) `logs`

**Field penting**
- `id`
- `system_id` (FK → systems, cascade delete)
- `feature_id` (nullable FK → features, nullOnDelete)
- `type` (enum): `progress | bug | fix | deployment | maintenance | decision | idea`
- `impact` (enum nullable): `low | medium | high | critical`
- `status` (string nullable; semantik bergantung type)
- `title`, `description`
- `logged_at` (timestamp, indexed)
- timestamps

**Catatan compatibility**
- `reference_log_id` masih ada di schema awal `logs` dan masih ada di model sebagai **deprecated**. Workflow terbaru memakai pivot `log_references`.

**Index**
- (`system_id`, `type`)
- (`system_id`, `impact`)
- (`system_id`, `feature_id`)
- `feature_id`
- `reference_log_id` (masih ada; untuk legacy/compat)

### 4) `log_references` (pivot)

**Field penting**
- `id`
- `log_id` (FK → logs, cascade delete)
- `reference_log_id` (FK → logs, cascade delete)
- timestamps

**Fungsi**
- Many-to-many self reference antar logs.
- Dipakai utama untuk workflow **multiple bug → 1 fix**.

**Constraint**
- Unique (`log_id`, `reference_log_id`)

---

## 🔗 C. Relasi Data

**System ↔ Feature**
- `systems (1) → (N) features`
- Implementasi:
  - `System::features()` hasMany
  - `Feature::system()` belongsTo

**System ↔ Logs**
- `systems (1) → (N) logs`
- Implementasi:
  - `System::logs()` hasMany
  - `Log::system()` belongsTo

**Feature ↔ Logs**
- `features (1) → (N) logs` (optional; log bisa global tanpa feature)
- Implementasi:
  - `Feature::logs()` hasMany
  - `Log::feature()` belongsTo (nullable secara data)

**Logs ↔ Logs (references)**
- Relasi utama (baru): `logs (N) ↔ (N) logs` via `log_references`
  - `Log::references()` = logs yang direferensikan oleh log ini
  - `Log::referencedBy()` = logs yang mereferensikan log ini
- Relasi legacy (deprecated): `Log::referenceLog()` via `reference_log_id`

---

## 🔄 D. Flow Utama Sistem

### 1) System lifecycle

**Create system**
- Route: `systems.create` → `SystemController@create`
- Simpan: `SystemController@store` (validasi `status`, optional `stage`, optional `released_at`).

**Update status/stage/released_at**
- Route: `systems.edit` → `SystemController@edit`
- Update: `SystemController@update`
  - Menggunakan transaksi `DB::transaction()` untuk:
    1) update data system
    2) (opsional) membuat log “assistive” jika payload `log` ada

**Assisted auto-logging**
- Di UI edit system, jika status/stage/released_at berubah, panel draft log muncul.
- User boleh isi log atau skip; jika diisi, log dibuat otomatis pada saat update system.

**Deployment/maintenance**
- Secara konsep dicatat melalui `logs` bertipe `deployment` / `maintenance` (global atau scoped feature).

### 2) Feature workflow

**Create feature**
- Nested route: `/systems/{system}/features/create` (`systems.features.create`)
- `FeatureController@store` auto-set `system_id` dari route.

**Update feature**
- `FeatureController@update` mengupdate fields; jika status `done` maka progress dipaksa 100 + `completed_at` diset (jika kosong).

**Status feature**
- Status manual via CRUD (`planned`, `in_progress`, `done`, `on_hold`).
- Ada mekanisme auto (lihat bagian “Progress System”) yang bisa mengubah progress/status berdasarkan logs.

### 3) Log workflow

**Create log**
- `LogController@create/store` + `LogRequest`
- Sistem memilih `system_id` + optional `feature_id`.

**Type log**
- Enum type: `progress, bug, fix, deployment, maintenance, decision, idea`
- Default status by type (controller):
  - `bug` → default `open`
  - `fix` → default `resolved`
  - `progress` → default `on_progress`

**Hubungan log (bug → fix)**
- Referensi multi log via pivot `log_references`.
- UI log form menyediakan modal selector untuk memilih reference logs:
  - Untuk `fix`: hanya boleh memilih **bug yang status open**.
  - Jika log scoped feature, reference juga harus 1 feature.

**Global vs Feature log**
- `feature_id = null` → global log (contoh: deployment/maintenance umum).
- `feature_id != null` → log scoped feature (progress/bug/fix per feature).

---

## 📊 E. Progress System

### Progress system (di UI)
- Di Systems Index dan Show, “Feature Completion” untuk system dihitung dari:
  - `SystemController@index` menggunakan `withAvg('features', 'progress')`
  - Ini berarti rata-rata progress **manual/tersimpan** pada tabel `features`.

### Progress feature (manual + auto)
- Secara default, `features.progress` bisa diubah manual lewat CRUD Feature.
- Ada auto-update progres lewat logs:
  - `Feature::recalculateProgressFromLogs()` dipanggil dari `LogController` setelah create/update/delete log.

**Logika auto-progress (saat ini)**
- `total` = jumlah logs pada feature dengan type `progress` atau `bug`
- `completed` =
  - jumlah logs type `progress` dengan status `done`
  - + jumlah logs type `fix` dengan status `resolved`
- `progress` = round((completed / total) * 100), di-clamp max 100
- Jika progress >= 100, feature diset `status = done` dan `completed_at` diisi (jika kosong)

**Catatan**
- Karena `completed` memasukkan fix, sementara `total` tidak memasukkan fix, progress bisa “naik cepat” dan sering mencapai 100 lebih cepat (tetap di-clamp 100). Ini bisa cocok untuk workflow tertentu, tapi perlu dipahami oleh tim.

---

## 🎨 F. UI / UX Flow

### Dashboard (`resources/js/Pages/Dashboard.vue`)
- Menampilkan ringkasan statistik berbasis logs dan system.
- Setelah refactor database, chart diganti sementara menjadi text summary (stabilitas).
- Recent logs & critical events punya fallback jika kosong.

### Systems Index (`resources/js/Pages/Systems/Index.vue`)
- Tabel modern (row clickable) menampilkan:
  - Name
  - Status (badge)
  - Stage (badge)
  - Features count
  - Logs count
  - Feature Completion (ProgressBar dari avg feature progress)

### System Show (`resources/js/Pages/Systems/Show.vue`)
- Layout section-based:
  - Header: nama system + status badge + stage badge + released + repo link
  - Summary cards: total feature, done, in progress, avg progress
  - Features list: status badge + progress bar + assigned team
  - Global logs: log tanpa feature
  - Logs by feature: accordion per feature

### Feature CRUD
- Nested di system:
  - Create/Edit via `resources/js/Pages/Features/Form.vue`
  - UX fokus: slider progress + auto set 100 jika status done

### Logs CRUD
- Create/Edit via `resources/js/Pages/Logs/Form.vue`
  - Feature select filtered by system
  - Reference selection via modal (multi select)
  - Status dropdown dinamis berdasarkan type
- Index: tabel log terbaru (dengan system + feature optional, badge type/impact/status)
- Show: detail log + list references (jika ada)

---

## 🧠 G. Desain yang Sudah Baik

- **Logs sebagai pusat timeline**: sesuai tujuan workstation; feature hanya pengelompokan.
- **Nested resource untuk feature**: `/systems/{system}/features` menjaga konteks dan UX “feature dikelola dari system”.
- **Centralized labels/maps di frontend**: `resources/js/Utils/enums.js` mengurangi hardcode dan menjaga konsistensi badge/label.
- **Assisted auto-logging** saat perubahan lifecycle system:
  - Assistive, tidak memaksa, dan tidak “auto-save tanpa user”.
  - Transaksi DB menjaga konsistensi perubahan system + log.
- **Smart references (pivot)**: desain pivot `log_references` mendukung real workflow (multi bug → one fix).
- **Null-safe UI**: banyak tempat menggunakan fallback/optional chaining sehingga stabil saat data kosong.

---

## ⚠️ H. Potensi Masalah / Gap

- **`reference_log_id` masih ada** di schema `logs` dan model sebagai deprecated:
  - Risiko kebingungan developer baru (“mana yang benar?”).
  - Seeder/factory masih menyentuh `reference_log_id` (sebagian sudah di-null-kan), tapi field tetap muncul di schema.

- **Semantik status sekarang “campuran”**:
  - `logs.status` sudah string (fleksibel), tapi UI/validator mengasumsikan subset status per type.
  - Jika ada status baru di masa depan, perlu update utils + validation + UI.

- **Auto-progress feature bisa terasa tidak intuitif**
  - Karena fix (resolved) ikut dihitung completed, sementara total hanya progress+bug.
  - Ini bisa menyebabkan feature cepat menjadi 100 meski progress “done” sedikit.

- **Performa validasi references**
  - `LogRequest` melakukan query untuk mengambil semua reference logs ketika `reference_ids` diisi.
  - Untuk skala besar, pendekatan ini perlu dibatasi (limit/UX selector sudah membatasi list, namun tetap perlu awareness).

- **Status vs Stage overlap**
  - `systems.status` “maintenance” adalah kondisi operasional.
  - `systems.stage` juga punya value “maintenance” untuk lifecycle.
  - Ini valid secara konsep, tapi bisa membingungkan tanpa guideline internal yang jelas.

- **SystemController@index memetakan system ke array**
  - Bagus untuk shaping payload, tapi perlu dijaga agar field baru tidak “hilang” tanpa sengaja ketika ditambahkan di model.

---

## 🚀 I. Potensi Improvement (tanpa implementasi)

### UX improvement (ringan)
- Tambahkan tooltip/helptext singkat di UI:
  - Bedakan “Status (operasional)” vs “Stage (lifecycle)”.
  - Jelaskan rule global log vs feature log.
- Di Log Form, sembunyikan/disable selector reference ketika type bukan `fix` (opsional) untuk fokus UX.

### Logic improvement (ringan)
- Definisikan guideline internal untuk auto-progress:
  - Apakah “resolved fix” memang harus mempengaruhi completion?
  - Atau completion dihitung hanya dari progress status `done`?
- Tambahkan event-driven recalculation:
  - Saat ini recalc dipanggil di controller; bisa dipindah ke observer/listener untuk konsistensi lintas entry point (tanpa mengubah fitur).

### Struktur improvement (minimal)
- Deprecation plan untuk `reference_log_id`:
  - Simpan untuk backward compatibility, tapi dokumentasikan “read-only legacy”.
  - Tambahkan catatan di docs/README internal agar tim tidak menggunakannya untuk fitur baru.
- Tambahkan `SystemRequest` (FormRequest) untuk validasi system agar controller lebih ringkas dan konsisten dengan Feature/Log request.

