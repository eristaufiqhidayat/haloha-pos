@extends('layouts.app')
@section('title','Laporan Stok Harian')
@section('content')<div class="toolbar">
<form>
<input type="date" name="date" value="{{ $date }}" required aria-label="Tanggal laporan">
<button>Tampilkan</button>
</form>@permission('inventory.export')<a class="btn" href="{{ route('reports.inventory.export',['date'=>$date]) }}">Ekspor CSV</a>@endpermission</div>
<div class="card scroll">
<table>
<thead>
<tr>
<th>SKU</th>
<th>Produk</th>
<th>Stok awal</th>
<th>Masuk</th>
<th>Terjual</th>
<th>Stok akhir</th>
</tr>
</thead>
<tbody>@forelse($rows as $row)<tr>
<td>{{ $row['product']->sku }}</td>
<td>{{ $row['product']->name }}</td>
<td>{{ $row['opening'] }}</td>
<td>{{ $row['incoming'] }}</td>
<td>{{ $row['sold'] }}</td>
<td>
<b>{{ $row['closing'] }}</b>
</td>
</tr>@empty<tr>
<td colspan="6">Belum ada produk.</td>
</tr>@endforelse</tbody>
</table>
<p class="muted">Stok akhir = stok awal + masuk − terjual. Stok awal berasal dari seluruh mutasi sebelum tanggal laporan.</p>
</div>@endsection
