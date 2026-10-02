# HALOHA POS — Laravel 12 + Blade

Aplikasi POS untuk satu toko, mata uang rupiah, zona waktu Asia/Jakarta. Semua data disimpan dalam database. Tidak membutuhkan npm/Vite karena CSS dan JavaScript berada di `public/`.

## Isi dan fitur

- Login/logout session, password hash, pembatasan percobaan login, CSRF, akun aktif/nonaktif.
- Pengguna dan satu kategori (role) per pengguna; matriks izin per modul dan tindakan.
- Kategori Administrator dilindungi; minimal satu administrator aktif harus tersedia.
- Produk, kategori produk, SKU unik, harga beli/jual, stok minimum, arsip produk.
- Stok masuk dan buku mutasi. Stok tidak boleh diedit langsung dari form produk.
- Kasir dengan pencarian, filter kategori, keranjang, diskon nominal, pembayaran tunai/QRIS/transfer, kembalian, dan struk cetak.
- Harga/HPP disalin saat transaksi, diskon dan stok divalidasi di server. Checkout atomic dengan row lock dan token idempotensi.
- Dashboard, laporan penjualan harian (omzet bersih, HPP, laba kotor, metode bayar) dan stok harian. Ekspor CSV.
- Migration, seeder akun, kategori, izin, sembilan produk, stok awal, dua transaksi contoh.

## Instalasi di Linux / macOS

Syarat: PHP 8.2 atau lebih baru, Composer 2, ekstensi PDO MySQL (atau SQLite), mbstring, XML, ctype, curl, fileinfo, openssl, tokenizer. MySQL 8 direkomendasikan untuk penggunaan banyak kasir.

```bash
unzip haloha-laravel12.zip
cd haloha-laravel12
composer install
cp .env.example .env
php artisan key:generate
```

Buat database baru, misalnya melalui phpMyAdmin atau MySQL:

```sql
CREATE DATABASE haloha_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Sesuaikan `.env`:

```dotenv
APP_NAME="HALOHA POS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=haloha_pos
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=sync
POS_SEED_PASSWORD=Haloha123!
POS_SEED_DEMO_SALES=true
```

Jika Laravel di container bersama MySQL, `DB_HOST` memakai nama service MySQL, bukan `localhost`.

```bash
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

Buka http://localhost:8000. Untuk percobaan SQLite, ganti `DB_CONNECTION=sqlite`, hapus baris `DB_DATABASE` agar menggunakan default `database/database.sqlite`, lalu buat file dengan `touch database/database.sqlite` sebelum migrate.

## Akun awal

| Kategori | Email | Akses awal |
| --- | --- | --- |
| Administrator | admin@haloha.test | Semua modul dan tindakan |
| Kasir | kasir@haloha.test | Kasir/transaksi dan lihat stok harian |
| Gudang | gudang@haloha.test | Produk, kategori produk, stok masuk, laporan stok |
| Pemilik | pemilik@haloha.test | Dashboard dan laporan penjualan/stok |

Password awal mengikuti `POS_SEED_PASSWORD` (contoh: `Haloha123!`). Ganti sebelum digunakan. Di production, variable ini wajib diisi. Atur `POS_SEED_DEMO_SALES=false` jika tidak ingin transaksi contoh; jalankan hanya `PermissionSeeder`, `RoleSeeder`, `UserSeeder` bila tidak membutuhkan produk contoh. Seeder tidak mengubah password akun atau saldo produk yang telah ada. Seeder Administrator menyelaraskan izin penuh; role lainnya hanya diberi izin default saat pertama dibuat.

## Alur penggunaan

1. Administrator membuat kategori pengguna dan memilih izin melalui Hak Akses.
2. Buat pengguna, tentukan kategori, status, dan password.
3. Buat kategori produk dan produk; catat persediaan melalui menu Stok.
4. Kasir memilih produk, mengisi diskon dan pembayaran, mencentang pembayaran diterima, lalu Bayar & simpan.
5. Struk dapat dicetak. Stok dan laporan langsung mengikuti transaksi.
6. Pilih tanggal untuk laporan penjualan/stok dan unduh CSV sesuai izin.

Hak akses diperiksa pada middleware/controller, bukan hanya disembunyikan di menu. Kasir dapat melihat struk miliknya; kategori dengan `sales.view` dapat melihat seluruh struk. Akun tanpa izin akan diarahkan ke halaman tanpa akses.

## Struktur kode

```text
app/Http/Controllers/     Auth, Dashboard, Pos, Product, Category, Stock,
                         User, Role, Permission, Report
app/Http/Middleware/      EnsureActiveUser, CheckPermission
app/Models/               User, Role, Permission, Category, Product, Sale,
                         SaleItem, StockMovement
app/Services/             SaleService, StockService, ReportService
config/pos.php            Daftar modul dan tindakan
routes/web.php            Route web, auth, dan middleware izin
resources/views/          Blade layout, form, kasir, struk, laporan
public/css/app.css        Tema biru tua dan layout responsif
public/js/pos.js          Keranjang dan loading submit
database/migrations/     Struktur database dan foreign key
database/seeders/        Data awal/demo
tests/Feature/PosTest.php Pengujian transaksi, stok, auth, izin dan laporan
```

## Database dan perhitungan

Tabel: `users`, `roles`, `permissions`, `permission_role`, `categories`, `products`, `sales`, `sale_items`, `stock_movements`, serta session/cache/job bawaan Laravel.

- Nilai uang berupa integer rupiah, tanpa pecahan.
- `sales.total = subtotal - discount`; laba kotor = total - total_cost.
- HPP memakai `products.cost_price` pada saat transaksi (harga standar, bukan FIFO/rata-rata tertimbang).
- `stock_movements.quantity` positif untuk awal/masuk, negatif untuk penjualan.
- Stok awal harian = seluruh mutasi sebelum awal hari. Stok akhir = awal + masuk - terjual.
- Waktu disimpan dan dilaporkan dengan zona Asia/Jakarta. Gunakan konfigurasi yang sama pada semua instance.
- Stok awal seeder dicatat pada kemarin; transaksi contoh pada waktu seeding.
- Produk diarsipkan, pengguna dihapus secara soft delete. Transaksi tersimpan dengan nama/harga/HPP snapshot.

Migration dalam paket membuat skema database baru. Tidak ada migrasi otomatis dari database POS lama atau localStorage mockup karena struktur dan file sumber lama belum disediakan. Untuk mengimpor data lama, gunakan mapping setelah backup; jangan menyalin saldo tanpa mutasi pembuka.

## Pengujian

```bash
php artisan test
php artisan route:list
php artisan view:cache
```

Test memakai SQLite in-memory terpisah, bukan database produksi. Row locking diuji melalui alur kode, tetapi pengujian konkurensi MySQL perlu dilakukan di server MySQL sebelum toko memakai beberapa kasir serentak.

## Deployment Nginx / cPanel

Document root harus folder `public/`, bukan root proyek. Composer vendor, `.env`, dan kode PHP jangan diakses publik. Tidak ada langkah `npm run build`.

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

Atur `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-anda`, `SESSION_SECURE_COOKIE=true`. Pastikan `storage` dan `bootstrap/cache` writable oleh PHP-FPM. Jangan memakai `chmod 777` sebagai pengaturan tetap.

Contoh konfigurasi Nginx untuk Docker ada di `deploy/nginx.conf.example`. Ubah port, document root, dan upstream PHP-FPM sesuai stack. Jika reverse proxy Cloudflare digunakan, tambahkan konfigurasi trusted proxy khusus jaringan proxy yang benar pada `bootstrap/app.php`; hindari memaksa HTTPS untuk seluruh lingkungan lokal.

## Batas cakupan

QRIS/transfer dicatat setelah kasir mengonfirmasi pembayaran; tidak terhubung payment gateway. Paket belum mencakup retur/pembatalan transaksi, pajak, multi cabang, satuan pecahan, metode HPP FIFO, atau sinkronisasi offline. Jangan mengubah stok atau menghapus transaksi langsung di database.

## Acuan framework

- https://laravel.com/docs/12.x/installation
- https://laravel.com/docs/12.x/database
- https://laravel.com/docs/12.x/middleware
