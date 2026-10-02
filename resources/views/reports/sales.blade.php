@extends('layouts.app')
@section('title','Laporan Penjualan Harian')
@section('content')<div class="toolbar">
<form>
<input type="date" name="date" value="{{ $date }}" required aria-label="Tanggal laporan">
<button>Tampilkan</button>
</form>@permission('sales.export')<a class="btn" href="{{ route('reports.sales.export',['date'=>$date]) }}">Ekspor CSV</a>@endpermission</div>@include('partials.stats')<div class="grid">
<div class="card">
<h3>Metode pembayaran</h3>@foreach(['cash'=>'Tunai','qris'=>'QRIS','transfer'=>'Transfer'] as $key=>$name)<div class="line">
<span>{{ $name }}</span>
<b>Rp {{ number_format($report['payments'][$key]??0,0,',','.') }}</b>
</div>@endforeach</div>
<div class="card">
<h3>Ringkasan pendapatan</h3>
<div class="line">
<span>HPP</span>
<b>Rp {{ number_format($report['cost'],0,',','.') }}</b>
</div>
<div class="line">
<span>Diskon</span>
<b>Rp {{ number_format($report['discount'],0,',','.') }}</b>
</div>
<p class="muted">Laba kotor = omzet setelah diskon − HPP. Belum dikurangi biaya operasional.</p>
</div>
</div>
<div class="card">
<h3>Detail transaksi</h3>@include('partials.sales-table')</div>@endsection
