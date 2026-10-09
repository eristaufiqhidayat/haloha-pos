# HALOHA POS Revisi 1

Revisi ini memakai warna corporate emas–charcoal dan logo aplikasi lama. Logo baru hanya pada struk pembayaran; tiket dapur tetap memakai logo lama.

Fitur yang terintegrasi:

- Transaksi Baru → Pesanan Berjalan → Bayar → Struk. Pesanan berjalan belum mengurangi stok atau pendapatan.
- Nama kasir disimpan saat pesanan dibuat; nama tamu, nomor meja dan take away dicetak pada struk.
- Notes per baris menu melalui dialog. Baris produk yang sama dengan catatan berbeda tetap dipisah.
- Checkbox diskon nominal rupiah dan pajak persentase. Pajak dihitung setelah diskon dan dibulatkan ke rupiah. Baris diskon/pajak hanya dicetak bila aktif.
- QRIS, transfer, debit dan tunai dapat digabung. Alokasi harus sama dengan tagihan. Kasir mengonfirmasi pembayaran nontunai setelah memeriksa bukti, dan kembalian hanya untuk tunai.
- Cetak dapur 80 mm tanpa harga; struk 80 mm memakai logo baru.
- Admin mengatur hak akses fitur per pengguna atau mengikuti kategori, serta produk yang boleh dijual. Pemeriksaan produk dilakukan di server, termasuk saat membayar pesanan berjalan.
- Laporan dan CSV menghitung setiap metode pembayaran dari alokasi, dengan pajak dipisahkan dari laba kotor.

## Memperbarui server

Jalankan dari direktori Laravel setelah merge:

```bash
git pull origin main
composer install --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan view:cache
```

Migrasi menambah kolom dan tabel pembayaran, mempertahankan transaksi lama sebagai lunas, serta memindahkan metode pembayaran lama ke rincian pembayaran. Tidak perlu menjalankan ulang seeder pada database produksi. Akun lama mempertahankan akses produk yang sudah ada saat migrasi; pengguna baru belum mendapat akses produk sampai admin mengaturnya melalui Pengguna → Atur akses. Produk baru perlu diberikan akses secara eksplisit.

QRIS/transfer/debit dicatat berdasarkan konfirmasi manual kasir; revisi ini tidak menghubungkan payment gateway, bank atau EDC. Cetak menggunakan dialog browser.

## Validasi

Pengujian Laravel mencakup idempotensi pembayaran, stok, rollback, alokasi split, konfirmasi nontunai, pajak, catatan item, akses produk dan kepemilikan pesanan. Workflow GitHub menjalankan pengujian dan kompilasi Blade sebelum merge.
