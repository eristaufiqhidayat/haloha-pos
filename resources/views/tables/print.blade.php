<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cetak QR Meja — HALOHA</title>
<link rel="stylesheet" href="{{ asset('css/app.css').'?v='.filemtime(public_path('css/app.css')) }}">
</head>
<body class="qr-print-page">
<div class="toolbar no-print"><button class="primary" onclick="window.print()">Cetak</button><a class="btn" href="{{ route('tables.index') }}">Kembali</a><span class="muted">{{ $tables->count() }} kartu QR meja aktif</span></div>
<div class="qr-cards">@forelse($tables as $t)<div class="qr-card"><img src="{{ asset('images/haloha-logo.jpeg') }}" alt="HALOHA" width="70" height="70"><div class="qr-card-title">Scan untuk pesan</div><div class="qr-card-code">{!! $qr[$t->id] !!}</div><div class="qr-card-table">MEJA {{ $t->number }}</div><div class="qr-card-note">Pilih menu dan kirim pesanan dari HP Anda</div></div>@empty<p>Tidak ada meja aktif.</p>@endforelse</div>
</body>
</html>
