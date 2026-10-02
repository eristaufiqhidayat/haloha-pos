<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','HALOHA POS') — HALOHA</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="shell">
<aside>
<h1>▦ HALOHA<span style="color:#7aa3ff">.</span>
</h1>
<small>Point of Sale</small>
<nav>
@foreach(['dashboard'=>['dashboard','Dashboard'],'pos'=>['pos.index','Kasir / POS'],'products'=>['products.index','Produk'],'categories'=>['categories.index','Kategori Produk'],'stock'=>['stock.index','Stok'],'sales'=>['reports.sales','Laporan Penjualan'],'inventory'=>['reports.inventory','Laporan Stok'],'users'=>['users.index','Pengguna'],'roles'=>['roles.index','Kategori Pengguna'],'permissions'=>['permissions.index','Hak Akses']] as $module=>[$route,$label])
@permission($module.'.view')<a href="{{ route($route) }}" class="{{ request()->routeIs($route) || (in_array($module,['products','users','roles']) && request()->routeIs($module.'.*')) ? 'active' : '' }}">{{ $label }}</a>@endpermission
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
