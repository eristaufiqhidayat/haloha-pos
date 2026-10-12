@extends('layouts.app')
@section('title','Laporan Penjualan')
@section('content')
@php
$rp = fn ($n) => 'Rp '.number_format($n,0,',','.');
$base = ['period'=>$filter['period'],'date'=>$filter['date'],'week'=>$filter['week'],'month'=>$filter['month'],'year'=>$filter['year'],'view'=>$filter['view']];
$itemQuery = $filter['view']==='item' ? array_filter(['category'=>$filter['category'],'sort'=>$filter['sort']]) : [];
@endphp
<p class="muted report-period">Periode: <b>{{ $filter['label'] }}</b></p>
<div class="toolbar">
<nav class="segmented" aria-label="Periode laporan">@foreach(['day'=>'Harian','week'=>'Mingguan','month'=>'Bulanan','year'=>'Tahunan'] as $key=>$name)<a href="{{ route('reports.sales',['period'=>$key]+$base) }}" class="{{ $filter['period']===$key ? 'active' : '' }}" @if($filter['period']===$key) aria-current="page" @endif>{{ $name }}</a>@endforeach</nav>
<form>
<input type="hidden" name="period" value="{{ $filter['period'] }}">
<input type="hidden" name="view" value="{{ $filter['view'] }}">
@switch($filter['period'])
@case('week')<input type="week" name="week" value="{{ $filter['week'] }}" required aria-label="Minggu laporan">@break
@case('month')<input type="month" name="month" value="{{ $filter['month'] }}" required aria-label="Bulan laporan">@break
@case('year')<input type="number" name="year" value="{{ $filter['year'] }}" min="2000" max="2100" required aria-label="Tahun laporan">@break
@default<input type="date" name="date" value="{{ $filter['date'] }}" required aria-label="Tanggal laporan">
@endswitch
<button>Tampilkan</button>
</form>@permission('sales.export')<a class="btn" href="{{ route('reports.sales.export',$base+$itemQuery) }}">Ekspor CSV</a>@endpermission</div>@include('partials.stats')<div class="grid">
<div class="card">
<h3>Metode pembayaran</h3>@foreach(['cash'=>'Tunai','qris'=>'QRIS','transfer'=>'Transfer','debit'=>'Debit'] as $key=>$name)<div class="line">
<span>{{ $name }}</span>
<b>{{ $rp($report['payments'][$key]??0) }}</b>
</div>@endforeach</div>
<div class="card">
<h3>Ringkasan pendapatan</h3>
<div class="line">
<span>HPP</span>
<b>{{ $rp($report['cost']) }}</b>
</div>
<div class="line">
<span>Diskon</span>
<b>{{ $rp($report['discount']) }}</b>
</div>
<div class="line"><span>Pajak</span><b>{{ $rp($report['rows']->sum('tax_amount')) }}</b></div>
<p class="muted">Laba kotor = omzet setelah diskon − pajak − HPP. Belum dikurangi biaya operasional.</p>
</div>
</div>
<div class="card">
<nav class="tabs" aria-label="Rincian laporan">@foreach(['category'=>'Per Kategori','item'=>'Per Item','transactions'=>'Detail Transaksi'] as $key=>$name)<a href="{{ route('reports.sales',['view'=>$key]+$base) }}" class="{{ $filter['view']===$key ? 'active' : '' }}" @if($filter['view']===$key) aria-current="page" @endif>{{ $name }}</a>@endforeach</nav>
@if($filter['view']==='category')
@php $gross = $categories->sum('revenue'); @endphp
<div class="scroll">
<table class="numeric">
<thead><tr><th>Kategori</th><th class="n">Jumlah item</th><th class="n">Qty terjual</th><th class="n">Omzet</th><th class="n">HPP</th><th class="n">Laba kotor</th><th class="n">Porsi omzet</th></tr></thead>
<tbody>@forelse($categories as $c)@php $share = $gross > 0 ? $c['revenue'] / $gross * 100 : 0; @endphp<tr>
<td><b>{{ $c['category'] }}</b></td>
<td class="n">{{ $c['items'] }}</td>
<td class="n">{{ number_format($c['quantity'],0,',','.') }}</td>
<td class="n">{{ $rp($c['revenue']) }}</td>
<td class="n">{{ $rp($c['cost']) }}</td>
<td class="n">{{ $rp($c['profit']) }}</td>
<td class="n"><span class="share"><i style="width:{{ round($share * 1.2) }}px"></i>{{ number_format($share,1,',','.') }}%</span></td>
</tr>@empty<tr><td colspan="7">Belum ada penjualan pada periode ini.</td></tr>@endforelse</tbody>
@if($categories->isNotEmpty())<tfoot><tr><td>Total</td><td class="n">{{ $categories->sum('items') }}</td><td class="n">{{ number_format($categories->sum('quantity'),0,',','.') }}</td><td class="n">{{ $rp($gross) }}</td><td class="n">{{ $rp($categories->sum('cost')) }}</td><td class="n">{{ $rp($categories->sum('profit')) }}</td><td class="n">100%</td></tr></tfoot>@endif
</table>
</div>
<p class="muted">Omzet per kategori dihitung dari harga item sebelum diskon transaksi ({{ $rp($report['discount']) }} pada periode ini) dan pajak, jadi totalnya bisa berbeda dari Omzet bersih di atas.</p>
@elseif($filter['view']==='item')
<form class="toolbar">
@foreach(['period','date','week','month','year','view'] as $k)<input type="hidden" name="{{ $k }}" value="{{ $base[$k] }}">@endforeach
<select name="category" aria-label="Filter kategori" onchange="this.form.submit()"><option value="">Semua kategori</option>@foreach($categoryOptions as $cat)<option value="{{ $cat->id }}" @selected($filter['category']===$cat->id)>{{ $cat->name }}</option>@endforeach</select>
<select name="sort" aria-label="Urutkan" onchange="this.form.submit()">@foreach(['qty'=>'Qty terbanyak','revenue'=>'Omzet tertinggi','profit'=>'Laba tertinggi'] as $k=>$name)<option value="{{ $k }}" @selected($filter['sort']===$k)>Urutkan: {{ $name }}</option>@endforeach</select>
<noscript><button>Terapkan</button></noscript>
</form>
<div class="scroll">
<table class="numeric">
<thead><tr><th>#</th><th>SKU</th><th>Produk</th><th>Kategori</th><th class="n">Qty</th><th class="n">Harga rata-rata</th><th class="n">Omzet</th><th class="n">HPP</th><th class="n">Laba kotor</th></tr></thead>
<tbody>@forelse($items as $i=>$x)<tr>
<td>{{ $i+1 }}</td>
<td>{{ $x['sku'] }}</td>
<td><b>{{ $x['name'] }}</b></td>
<td><span class="badge neutral">{{ $x['category'] }}</span></td>
<td class="n">{{ number_format($x['quantity'],0,',','.') }}</td>
<td class="n">{{ $rp($x['quantity'] ? $x['revenue'] / $x['quantity'] : 0) }}</td>
<td class="n">{{ $rp($x['revenue']) }}</td>
<td class="n">{{ $rp($x['cost']) }}</td>
<td class="n">{{ $rp($x['profit']) }}</td>
</tr>@empty<tr><td colspan="9">Belum ada penjualan pada periode ini.</td></tr>@endforelse</tbody>
@if($items->isNotEmpty())<tfoot><tr><td colspan="4">Total</td><td class="n">{{ number_format($items->sum('quantity'),0,',','.') }}</td><td></td><td class="n">{{ $rp($items->sum('revenue')) }}</td><td class="n">{{ $rp($items->sum('cost')) }}</td><td class="n">{{ $rp($items->sum('profit')) }}</td></tr></tfoot>@endif
</table>
</div>
<p class="muted">Omzet per item dihitung dari harga jual sebelum diskon transaksi dan pajak.</p>
@else
@include('partials.sales-table',['showDate'=>$filter['period']!=='day','emptyText'=>'Belum ada transaksi pada periode ini.'])
@endif
</div>@endsection
