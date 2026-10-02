@extends('layouts.app')
@section('title','Kategori Produk')
@section('content')@permission('categories.create')<div class="card">
<form method="post" action="{{ route('categories.store') }}" class="toolbar">@csrf<label>Nama kategori <input name="name" required maxlength="100" value="{{ old('name') }}">
</label>
<button class="primary">Tambah kategori</button>
</form>
</div>@endpermission<div class="card scroll">
<table>
<thead>
<tr>
<th>Nama</th>
<th>Jumlah produk</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>@forelse($categories as $c)<tr>
<td>{{ $c->name }}</td>
<td>{{ $c->products_count }}</td>
<td>
<div class="actions">@permission('categories.update')<form method="post" action="{{ route('categories.update',$c) }}">@csrf @method('PUT')<input name="name" value="{{ $c->name }}" aria-label="Nama kategori {{ $c->name }}" required maxlength="100">
<button>Ubah</button>
</form>@endpermission @permission('categories.delete')<form method="post" action="{{ route('categories.destroy',$c) }}" onsubmit="return confirm('Hapus kategori ini?')">@csrf @method('DELETE')<button class="danger">Hapus</button>
</form>@endpermission</div>
</td>
</tr>@empty<tr>
<td colspan="3">Belum ada kategori.</td>
</tr>@endforelse</tbody>
</table>
</div>@endsection
