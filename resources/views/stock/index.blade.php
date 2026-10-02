@extends('layouts.app')
@section('title','Stok')
@section('content')@permission('stock.create')<div class="card">
<h3>Catat stok masuk</h3>
<form class="form" method="post" action="{{ route('stock.store') }}">@csrf<div class="grid">
<label>Produk<select name="product_id" required>
<option value="">Pilih produk</option>@foreach($products as $p)<option value="{{ $p->id }}" @selected(old('product_id')==$p->id)>{{ $p->name }} ({{ $p->sku }}) · stok {{ $p->stock }}</option>@endforeach</select>
</label>
<label>Jumlah (unit)<input name="quantity" type="number" min="1" max="1000000" value="{{ old('quantity') }}" required>
</label>
</div>
<label>Catatan / referensi penerimaan<input name="note" maxlength="500" value="{{ old('note') }}">
</label>
<button class="primary">Simpan stok masuk</button>
</form>
</div>@endpermission<div class="card">
<h3>Riwayat mutasi</h3>
<div class="scroll">
<table>
<thead>
<tr>
<th>Waktu</th>
<th>Produk</th>
<th>Jenis</th>
<th>Jumlah</th>
<th>Stok sesudah</th>
<th>Petugas</th>
<th>Catatan</th>
</tr>
</thead>
<tbody>@forelse($movements as $m)<tr>
<td>{{ $m->occurred_at->format('d/m/Y H:i') }}</td>
<td>{{ $m->product->name }}</td>
<td>{{ ['opening'=>'Stok awal','incoming'=>'Masuk','sale'=>'Penjualan'][$m->type]??$m->type }}</td>
<td>{{ $m->quantity>0?'+':'' }}{{ $m->quantity }}</td>
<td>{{ $m->stock_after }}</td>
<td>{{ $m->user?->name??'Sistem' }}</td>
<td>{{ $m->note }}</td>
</tr>@empty<tr>
<td colspan="7">Belum ada mutasi.</td>
</tr>@endforelse</tbody>
</table>
</div>
<div class="pagination">{{ $movements->links('pagination::simple-default') }}</div>
</div>@endsection
