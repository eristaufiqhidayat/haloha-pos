# Pesan dari QR Meja

Tamu dine in scan QR di meja, nomor meja langsung terisi, memilih menu dari HP, lalu memilih bayar QRIS dari HP atau bayar di kasir.

## Alur

1. Admin membuka **Meja & QR**, menambah meja, lalu **Cetak** kartu QR untuk ditempel di meja. QR berisi tautan acak per meja (`/m/<token>`), bukan nomor meja, jadi tidak bisa ditebak. **Ganti QR** mematikan tautan lama.
2. Tamu scan QR dan memesan tanpa login. Harga dan stok dihitung di server; nomor meja diambil dari token QR.
3. Pesanan masuk ke **Pesanan Berjalan** di bagian *Pesanan dari QR meja* (angka merah di sidebar). Stok belum terpotong.
   - **Bayar di kasir**: kasir menekan *Konfirmasi*; pesanan menjadi pesanan berjalan milik kasir itu (cetak dapur, split bill, bayar seperti biasa).
   - **QRIS**: tamu melihat QR dengan nominal pesanan + kode unik Rp 1–99, membayar, lalu menekan *Saya sudah bayar*. Kasir mencocokkan dana di aplikasi bank lalu menekan *Dana masuk, proses*; pesanan lunas (metode QRIS), stok terpotong, dan tiket dapur dicetak dari struk. *Bayar di kasir saja* mengubahnya menjadi pesanan berjalan biasa.
4. Pesanan QRIS yang tidak dibayar tamu dalam 30 menit (`pos.qr_order.qris_expire_minutes`) batal otomatis. Kasir juga bisa *Tolak*.

## QRIS

Upload foto QRIS statis resto di **Meja & QR**. Foto dibaca di browser, lalu server memeriksa format dan CRC. Untuk tiap pesanan sistem membuat QR dengan nominal (tag 54, mode dinamis) dari QRIS tersebut; dana tetap masuk ke merchant yang sama. Konfirmasi lunas dilakukan manual oleh kasir karena belum ada payment gateway. Kode unik tidak masuk omzet; tercatat sebagai "QRIS diterima" di struk.

## Memperbarui server

```bash
git pull origin main
composer install --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan view:cache
```

Migrasi menambah tabel `dining_tables` dan `app_settings`, kolom sumber pesanan di `sales`, membuat `sales.user_id` boleh kosong (pesanan QR sebelum dikonfirmasi), serta menambah hak akses **Meja & QR**. Administrator langsung mendapat akses; kategori pengguna lain diatur di Hak Akses.

QR memakai alamat aplikasi yang sedang dibuka admin saat mencetak, jadi cetak dari alamat yang bisa dibuka HP tamu (domain publik atau IP Wi-Fi resto).
