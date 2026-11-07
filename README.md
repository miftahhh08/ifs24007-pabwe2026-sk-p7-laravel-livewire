# Aplikasi Stock Barang (Laravel 13 + Livewire 4)

Studi kasus **pabwe-2026-p7** — `ifs24007-pabwe2026-sk-p7-laravel-livewire`.
Arsitektur Feature-First, Livewire 4, MySQL, PhpSpreadsheet (Excel), integrasi OpenAI, tes dengan coverage 100%, CI Jenkins.

## Fitur

| Peran | Kemampuan |
|---|---|
| Guest | Login, register. `/` diarahkan ke login |
| Member | Kelola **stok miliknya sendiri**: cari, filter status, pagination, CRUD (modal), adjust qty (+/−), detail + cover + deskripsi Markdown, import/export Excel, generate item dengan AI, profil (data, password, foto) |
| Admin | `/admin/users` (daftar user + jumlah stok) dan `/admin/users/{user}/stocks` (stok user, **hanya baca**) |

Status stok dihitung otomatis dari `quantity` vs `min_stock`: `available` (hijau), `low_stock` (kuning), `out_of_stock` (merah).

## Akun demo (dari seeder)

| Username | Password | Peran |
|---|---|---|
| `admin` | `admin` | admin |
| `member` | `member` | member (punya 4 contoh stok) |

## Prasyarat

- PHP ≥ 8.4 (sesuai `composer.lock`) dengan ekstensi: `gd`, `zip`, `mbstring`, `fileinfo`, `curl`, `pdo_mysql`, `pdo_sqlite`, `openssl`
- Composer, Node.js + npm, MySQL/MariaDB
- Untuk coverage: ekstensi `pcov` **atau** `xdebug`

Cek: `php -v`, `php -m`, `composer -V`, `node -v`, `mysql --version`.

## Cara menjalankan (pertama kali)

```powershell
# 1. Ambil dependensi
composer install
npm install --ignore-scripts

# 2. Siapkan environment
copy .env.example .env
php artisan key:generate

# 3. Buat database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS pabwe_p7_stock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# lalu cek DB_USERNAME / DB_PASSWORD di .env

# 4. Migrasi + data awal
php artisan migrate:fresh --seed

# 5. Link storage (foto profil & cover stok) dan build aset
php artisan storage:link
npm run build

# 6. Jalankan
php artisan serve
```

Buka <http://localhost:8000> lalu login dengan akun demo.

Selama mengembangkan tampilan, jalankan `npm run dev` di terminal kedua.

### Mengaktifkan fitur AI (opsional)

Isi di `.env` (jangan pernah di-commit):

```
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini
OPENAI_BASE_URL=https://api.openai.com/v1
```

Atau jalankan `powershell -ExecutionPolicy Bypass -File .\setup-openai-env.ps1`.
Tanpa API key, tombol generate menampilkan pesan gagal, dan fitur lain tetap normal.

### HTTPS

Set `FORCE_HTTPS=true` di `.env` bila aplikasi berjalan di balik HTTPS (produksi).

## Menjalankan tes

```powershell
php vendor/bin/phpunit      # tes saja (tanpa coverage)
composer test               # tes + ringkasan coverage (butuh pcov/xdebug)
composer audit              # cek kerentanan dependensi
```

Hasil yang diharapkan: **176 tests OK**, coverage `app/` **Classes/Methods/Lines 100%**, tanpa PHPUnit notice, audit bersih.
Tes memakai SQLite in-memory (lihat `phpunit.xml`), jadi tidak menyentuh database MySQL-mu.

### Mengaktifkan ekstensi PHP (Windows)

Edit `php.ini` (lokasi: `php --ini`), hapus tanda `;` pada:

```
extension=gd
extension=zip
extension=mbstring
extension=fileinfo
```

Coverage dengan pcov:
```
extension=pcov
pcov.enabled=1
pcov.directory=app
```
atau dengan Xdebug: `zend_extension=xdebug` dan `xdebug.mode=coverage`.
Verifikasi: `php -m | findstr /i "gd pcov xdebug"`.

## Daftar periksa verifikasi manual

1. Login `member/member` → masuk ke `/stocks`; login `admin/admin` → masuk ke `/admin/users`.
2. Register user baru → otomatis berperan member, diarahkan ke login.
3. **Member**: tambah, ubah, hapus stok; cari nama/SKU; filter status; tombol + / − mengubah qty dan status (qty tidak pernah di bawah 0).
4. Buka detail stok: edit deskripsi Markdown (Toast UI), unggah dan hapus cover, adjust qty.
5. Export Excel → buka file; import ulang file tersebut (status dihitung ulang dari qty).
6. Generate AI → preview → pilih sebagian → simpan (butuh API key).
7. Profil: ubah data, ganti password, unggah dan hapus foto.
8. **Admin**: lihat daftar user dan jumlah stok; buka stok user (tidak ada tombol ubah/hapus).
9. Keamanan: sebagai member A buka `/stocks/{id milik B}` → 403; sebagai member buka `/admin/users` → 403; sebagai admin buka `/stocks` → 403.

## Struktur proyek (Feature-First)

```
app/Features/
  Admin/    Livewire (UserList, AdminUserStockList), Services/AdminUserService
  Ai/       Contracts/AiClientInterface, DTO/GeneratedStockItem, Services/OpenAiStockGeneratorService
  Auth/     Actions (Login/Register), Livewire (Login, Register)
  Profile/  Livewire/Profile, Services/ProfileService
  Stock/    Contracts, Enums/StockStatus, Livewire (StockList, StockDetail), Models/Stock,
            Policies/StockPolicy, Repositories, Services (StockService, StockExcelService)
app/Http/Middleware/  EnsureRole (alias role), EnsureStockOwnership (alias owns.stock)
tests/                Unit + Feature, mirror struktur app
Jenkinsfile           CI: audit, gate coverage 100%, Trivy, paket zip
```

Rute: lihat `routes/web.php` atau `php artisan route:list`. Rate limit `1000-per-second` dipasang di semua rute web.

## CI (Jenkins)

`Jenkinsfile` menjalankan: install dependensi → `composer audit` (gagal bila ada kerentanan) → build aset → PHPUnit dengan coverage (gagal bila < 100%) → Trivy → zip tanpa `vendor`.
Image CI harus punya `pcov`/`xdebug`, `gd`, `zip`, `mbstring`, `pdo_sqlite`, dan akses internet (build Vite mengunduh font dari `fonts.bunny.net`).

## Pemecahan masalah

| Gejala | Penyebab / solusi |
|---|---|
| `No code coverage driver available` | Aktifkan pcov/xdebug, atau pakai `php vendor/bin/phpunit` tanpa coverage |
| `imagejpeg function is not defined` | GD tidak punya dukungan JPEG (mis. Herd Lite). Tes sudah memakai PNG; pastikan folder `tests/` diganti dengan versi terbaru |
| `Vite manifest not found` saat membuka halaman | Jalankan `npm run build` (atau `npm run dev`) |
| Foto/cover tidak tampil | Jalankan `php artisan storage:link` |
| `SQLSTATE ... Unknown database` | Buat database `pabwe_p7_stock` dan cek `DB_*` di `.env` |
| `npm run build` gagal `fetch failed` | Perlu internet ke `fonts.bunny.net` |
| AI gagal | Cek `OPENAI_API_KEY`, kuota, dan koneksi; detail galat ada di `storage/logs/laravel.log` |
| Perubahan `.env` tidak terbaca | `php artisan config:clear` |

## Catatan keamanan

- Jangan commit `.env` atau API key. Artefak uji (`coverage-*`, `junit.xml`, `phpunit_*.txt`, dst.) sudah di `.gitignore`.
- Import Excel dan export memperlakukan sel sebagai teks (tanpa eksekusi formula).
- Deskripsi Markdown dirender dengan HTML mentah dibuang.
