# Tahap 1 + 2 (database, model, auth, middleware, policy, layout)

1. Ekstrak zip ini ke ROOT proyek (timpa file yang sama):
   PowerShell: Expand-Archive -Force tahap-1-2.zip -DestinationPath .

2. Edit manual config/app.php: tambahkan satu baris tepat setelah baris 'url' => env('APP_URL', ...),
       'force_https' => (bool) env('FORCE_HTTPS', false),

3. Pastikan .env: DB_CONNECTION=mysql, DB_DATABASE=pabwe_p7_stock, FORCE_HTTPS=false

4. Jalankan:
       php artisan optimize:clear
       php artisan migrate:fresh --seed
       npm run build
       php artisan serve

5. Uji: buka http://127.0.0.1:8000 -> redirect ke /login. Login admin/admin atau member/member.
   (/stocks dan /admin/users masih 404 sampai Tahap 3-4, itu normal.)
