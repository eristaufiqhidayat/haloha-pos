<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
@stack('head')
<title>@yield('title') — {{ config('pos.receipt.name') }}</title>
<link rel="stylesheet" href="{{ asset('css/app.css').'?v='.filemtime(public_path('css/app.css')) }}">
</head>
<body class="guest">
<header class="guest-head"><div><b>{{ config('pos.receipt.name') }}</b><small>{{ config('pos.receipt.address') }}</small></div><span class="table-pill">Meja {{ $table->number }}</span></header>
<main class="guest-main">
@if($errors->any())<div class="alert error" role="alert"><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
@yield('content')
</main>
@stack('scripts')
</body>
</html>
