<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','HALOHA POS') — HALOHA</title>
<link rel="stylesheet" href="{{ asset('css/app.css').'?v='.filemtime(public_path('css/app.css')) }}">
</head>
<body>
<div class="shell">
<aside>
<a class="brand-link" href="{{ url('/') }}" aria-label="HALOHA POS"><img class="brand-logo" src="{{ asset('images/haloha-logo.jpeg') }}" alt="Haloha — Halal, Original, Happiness" width="447" height="447"></a>
<small class="brand-caption">Point of Sale</small>
<nav>
@php($qrWaiting = auth()->user()->hasPermission('pos.view') ? \App\Models\Sale::awaitingCashier()->count() : 0)
@foreach(['dashboard'=>['dashboard','Dashboard'],'pos'=>['pos.index','Transaksi Baru'],'orders'=>['orders.index','Pesanan Berjalan','pos'],'products'=>['products.index','Produk'],'categories'=>['categories.index','Kategori Produk'],'stock'=>['stock.index','Stok'],'tables'=>['tables.index','Meja & QR'],'sales'=>['reports.sales','Laporan Penjualan'],'inventory'=>['reports.inventory','Laporan Stok'],'users'=>['users.index','Pengguna'],'roles'=>['roles.index','Kategori Pengguna'],'permissions'=>['permissions.index','Hak Akses']] as $module=>$item)
@php([$route,$label] = $item)
@permission(($item[2] ?? $module).'.view')<a href="{{ route($route) }}" class="{{ request()->routeIs($route) || (in_array($module,['products','users','roles','orders','tables']) && request()->routeIs($module.'.*')) ? 'active' : '' }}">{{ $label }}@if($module==='orders' && $qrWaiting)<span class="nav-count" title="Pesanan dari QR meja menunggu kasir">{{ $qrWaiting }}</span>@endif</a>@endpermission
@endforeach
</nav>
<div class="aside-user">
<b>{{ auth()->user()->name }}</b>
<br>
<small>{{ auth()->user()->role->name }}</small>
</div>
</aside>
<main>
<header>
<h2>@yield('title','HALOHA POS')</h2>
<form method="post" action="{{ route('logout') }}">@csrf<button>Keluar</button>
</form>
</header>
@if(session('success'))<div class="alert" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert error" role="alert">
<ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
</div>@endif
@yield('content')</main>
</div>@stack('scripts')</body>
</html>
