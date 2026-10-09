<?php

return [
    'receipt' => ['name' => 'Haloha Kitchen', 'address' => 'Jl. Malaka Baru 4A – Pd Kopi', 'phone' => '0813 6000 9898'],
    'modules' => [
        'dashboard' => ['label' => 'Dashboard', 'actions' => ['view' => 'Lihat']],
        'pos' => ['label' => 'Kasir / POS', 'actions' => ['view' => 'Lihat', 'create' => 'Transaksi']],
        'products' => ['label' => 'Produk', 'actions' => ['view' => 'Lihat', 'create' => 'Tambah', 'update' => 'Ubah', 'delete' => 'Arsipkan']],
        'categories' => ['label' => 'Kategori Produk', 'actions' => ['view' => 'Lihat', 'create' => 'Tambah', 'update' => 'Ubah', 'delete' => 'Hapus']],
        'stock' => ['label' => 'Stok', 'actions' => ['view' => 'Lihat', 'create' => 'Stok masuk']],
        'sales' => ['label' => 'Laporan Penjualan', 'actions' => ['view' => 'Lihat', 'export' => 'Ekspor']],
        'inventory' => ['label' => 'Laporan Stok', 'actions' => ['view' => 'Lihat', 'export' => 'Ekspor']],
        'users' => ['label' => 'Pengguna', 'actions' => ['view' => 'Lihat', 'create' => 'Tambah', 'update' => 'Ubah', 'delete' => 'Hapus']],
        'roles' => ['label' => 'Kategori Pengguna', 'actions' => ['view' => 'Lihat', 'create' => 'Tambah', 'update' => 'Ubah', 'delete' => 'Hapus']],
        'permissions' => ['label' => 'Hak Akses', 'actions' => ['view' => 'Lihat', 'update' => 'Ubah']],
    ],
];
