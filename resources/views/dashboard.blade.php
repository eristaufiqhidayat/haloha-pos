@extends('layouts.app')
@section('title','Dashboard')
@section('content')<div class="toolbar">
<span class="muted">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} · Ringkasan toko hari ini</span>@permission('pos.view')<a class="btn primary" href="{{ route('pos.index') }}">Transaksi baru</a>@endpermission</div>@include('partials.stats')<div class="card">
<h3>Transaksi hari ini</h3>@include('partials.sales-table')</div>
<div class="card">
<h3>Stok menipis</h3>
<div class="scroll">
<table>
<thead>
<tr>
<th>Produk</th>
<th>SKU</th>
<th>Stok</th>
<th>Batas minimum</th>
</tr>
</thead>
<tbody>@forelse($lowStock as $p)<tr>
<td>{{ $p->name }}</td>
<td>{{ $p->sku }}</td>
<td>
<span class="badge warn">{{ $p->stock }}</span>
</td>
<td>{{ $p->minimum_stock }}</td>
</tr>@empty<tr>
<td colspan="4">Seluruh stok di atas batas minimum.</td>
</tr>@endforelse</tbody>
</table>
</div>
</div>@endsection
