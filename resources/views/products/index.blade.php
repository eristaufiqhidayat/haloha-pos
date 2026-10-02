@extends('layouts.app')
@section('title','Produk')
@section('content')<div class="toolbar">
<form>
<input name="q" value="{{ $q }}" placeholder="Cari nama / SKU" aria-label="Cari produk">
<button>Cari</button>
</form>@permission('products.create')<a class="btn primary" href="{{ route('products.create') }}">+ Produk baru</a>@endpermission</div>
<div class="card scroll">
<table>
<thead>
<tr>
<th>Produk / SKU</th>
<th>Kategori</th>
<th>Harga beli</th>
<th>Harga jual</th>
<th>Stok</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>@forelse($products as $p)<tr>
<td>
<b>{{ $p->name }}</b>
<br>
<small>{{ $p->sku }}</small>
</td>
<td>{{ $p->category->name }}</td>
<td>Rp {{ number_format($p->cost_price,0,',','.') }}</td>
<td>Rp {{ number_format($p->selling_price,0,',','.') }}</td>
<td>
<span class="badge {{ $p->stock<=$p->minimum_stock?'warn':'' }}">{{ $p->stock }}</span>
</td>
<td>{{ $p->is_active?'Aktif':'Arsip' }}</td>
<td>
<div class="actions">@permission('products.update')<a class="btn" href="{{ route('products.edit',$p) }}">Ubah</a>@endpermission @permission('products.delete')@if($p->is_active)<form method="post" action="{{ route('products.destroy',$p) }}" onsubmit="return confirm('Arsipkan produk ini?')">@csrf @method('DELETE')<button class="danger">Arsipkan</button>
</form>@endif
@endpermission</div>
</td>
</tr>@empty<tr>
<td colspan="7">Belum ada produk.</td>
</tr>@endforelse</tbody>
</table>
<div class="pagination">{{ $products->links('pagination::simple-default') }}</div>
</div>@endsection
