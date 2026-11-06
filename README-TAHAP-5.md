# Tahap 5 - Profil + Admin (read-only)

1. Ekstrak ke ROOT proyek (timpa routes/web.php):
   Expand-Archive -Force .\tahap-5.zip -DestinationPath .
2. Jalankan:
   php artisan optimize:clear
   npm run build
   php artisan serve
3. Uji:
   - member/member -> /profile: ubah nama/username/email, ubah password, upload/hapus foto.
   - admin/admin -> /admin/users: cari user, lihat jumlah stok; "Lihat stok" -> /admin/users/{id}/stocks (tanpa tombol ubah/hapus).
   - admin buka /stocks -> 403; member buka /admin/users -> 403.
