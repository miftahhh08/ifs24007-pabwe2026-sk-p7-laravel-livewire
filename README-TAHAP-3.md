# Tahap 3 - Modul Stock (list, detail, CRUD, adjust qty, cover, Markdown)

1. Ekstrak ke ROOT proyek (timpa file yang sama):
   Expand-Archive -Force .\tahap-3.zip -DestinationPath .
2. Jalankan:
   php artisan optimize:clear
   php artisan storage:link        (lewati jika sudah pernah)
   npm run build                   (view baru = kelas Tailwind baru)
   php artisan serve
3. Login member/member -> otomatis ke /stocks. Uji: cari, filter status, tambah/edit/hapus,
   tombol +/-, buka Detail (edit dengan Toast UI, upload/hapus cover).
