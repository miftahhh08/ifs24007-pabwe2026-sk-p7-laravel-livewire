# Tahap 6 - Pengujian (coverage 100%) + CI

1. Ekstrak ke ROOT proyek (timpa file yang sudah ada):
   Expand-Archive -Force .\tahap-6.zip -DestinationPath .
2. Jalankan:
   composer dump-autoload
   php artisan config:clear
   composer test
3. Hasil yang diharapkan:
   - OK (176 tests, 511 assertions), tanpa PHPUnit notice
   - Coverage app/: Classes 100%, Methods 100%, Lines 100%
   (butuh driver coverage: pcov atau xdebug; ext gd, zip, mbstring, pdo_sqlite)
4. composer audit  -> harus "No security vulnerability advisories found"

Isi tahap ini:
- README.md         : panduan lengkap menjalankan, menguji, verifikasi manual
- tests/            : 24 file uji (Unit + Feature), mirror struktur latihan, namespace Stock
- Jenkinsfile       : audit gagal bila ada kerentanan, gate coverage 100%, Trivy, zip tanpa vendor
- config/app.php    : tambah 'force_https' (sebelumnya belum ada, FORCE_HTTPS di .env tidak berefek)
- .env.example      : MySQL pabwe_p7_stock, FORCE_HTTPS, OPENAI_*
- .gitignore        : abaikan artefak uji (coverage-*, junit.xml, phpunit_*.txt, trivy, latest-app.zip)
- phpunit.xml       : (tidak diubah dari bawaan; laporan coverage dibuat lewat flag di composer test / Jenkinsfile)
- composer.json     : script "test" => phpunit --coverage-text
- database/factories/UserFactory.php : tambah state member()
- tests/TestCase.php: withoutVite() agar uji tidak bergantung pada npm run build
- test.bat          : pintasan Windows
- public/favicon.svg + link di layout : ikon tab (opsional di modul)

Catatan lingkungan lokal (bukan bug kode):
- Ekstensi `gd` wajib aktif (extension=gd di php.ini) untuk tes upload gambar.
- `composer test` butuh driver coverage (pcov/xdebug). Tanpa driver, jalankan: php vendor/bin/phpunit

PENTING: hapus folder tests\ lama sebelum mengekstrak, supaya tidak tercampur file tes versi lain:
   Remove-Item -Recurse -Force .\tests
   Expand-Archive -Force .\tahap-6.zip -DestinationPath .
