# Tahap 4 - Excel (impor/ekspor) + AI generator

1. Ekstrak ke ROOT proyek (timpa file yang sama):
   Expand-Archive -Force .\tahap-4.zip -DestinationPath .
2. Tambahkan konfigurasi OpenAI otomatis ke .env dan .env.example:
   powershell -ExecutionPolicy Bypass -File .\setup-openai-env.ps1
   (skrip akan menanyakan API key; Enter jika belum punya. Key hanya ditulis ke .env, tidak pernah ke .env.example.)
3. Pastikan ekstensi PHP aktif: gd, zip, mbstring (php -m)
4. Jalankan:
   php artisan optimize:clear
   npm run build
   php artisan serve
5. Uji di /stocks (login member/member): Ekspor Excel -> ubah file -> Impor Excel; Generate AI -> centang -> Simpan terpilih.
