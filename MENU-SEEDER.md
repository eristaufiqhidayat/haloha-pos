# Seeder menu resmi HALOHA — 01 Juli 2026

Sumber: `Menu Haloha Up. 01 Juli 2026.pdf` dan `Menu Resto Up. 01 Juli 2026.pdf`.
Katalog berisi **161 produk dalam 24 kategori**: 38 produk / 8 kategori Haloha dan 123 produk / 16 kategori Resto. Harga adalah rupiah utuh sesuai PDF.

## Jalankan

Setelah migrasi aplikasi dijalankan, impor kategori dan produk:

```bash
php artisan db:seed --class=HalohaMenuSeeder
```

Untuk hanya kategori:

```bash
php artisan db:seed --class=HalohaMenuCategorySeeder
```

`HalohaMenuProductSeeder` juga dapat dijalankan sendiri; kategori dibuat otomatis. Pada lingkungan production, tambahkan `--force` setelah memastikan koneksi database tujuan sudah benar. Seeder tidak membutuhkan akun demo/admin.

## Aturan impor

- Prefix kategori `Haloha -` dan `Resto -` memisahkan dua daftar sumber. SKU permanen `HH-{kode}-{nomor}` / `RST-{kode}-{nomor}` dicantumkan eksplisit di `HalohaMenuCatalog.php`; jangan menomori ulang SKU setelah digunakan.
- Satu baris harga PDF menjadi satu produk. Ukuran oz/ml, jumlah pcs, level, dan porsi dibawa ke nama produk. Pilihan Hot/Ice, dada/paha (D/P), metode masak dalam paket, rasa Mix Jus, dan pilihan saus tetap satu produk; pilihannya ditulis melalui catatan per item saat memesan. Rice Bowl sudah termasuk nasi dan salad; Paket Nasi sudah termasuk es teh manis.
- Ejaan sumber seperti Cappucino, Shirmp, Karage, Bolognase, dan Toping dipertahankan. Nama boleh diedit admin setelah impor.
- Impor ulang hanya menambahkan SKU/kategori yang belum ada. Nama, kategori, harga jual, HPP, stok, status aktif, minimum stok, dan ID produk yang sudah ada tidak diubah. Perubahan harga selanjutnya dilakukan admin; menjalankan ulang seeder bukan sinkronisasi harga.
- Produk baru aktif, stok **0**, HPP **0** sebagai placeholder belum diisi, dan minimum stok **0**. Isi HPP dan stok melalui aplikasi sebelum dipakai berjualan; margin belum representatif sebelum HPP diisi. Seeder tidak membuat pergerakan stok fiktif.
- Hak akses kasir tidak diubah. Admin harus memberikan akses produk baru kepada kasir yang memakai daftar produk terbatas.
- Produk demo `HL-001` s.d. `HL-009`, transaksi lama, dan `DatabaseSeeder` tidak diubah/dihapus. Katalog resmi tidak otomatis menyatukan produk bernama mirip dari dua sumber maupun data lama. Nonaktifkan produk demo melalui admin jika tidak dipakai; jangan hapus produk yang sudah mempunyai transaksi.

## Verifikasi

`php artisan test --filter=HalohaMenuSeederTest` menguji jumlah kategori/produk, harga/varian representatif, impor tanpa pengguna demo, impor berulang, perlindungan stok/HPP/harga/status, dan akses kasir. Seluruh suite aplikasi juga dijalankan di CI.
