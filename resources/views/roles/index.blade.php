@extends('layouts.app')
@section('title','Kategori Pengguna')
@section('content')@permission('roles.create')<div class="toolbar">
<a class="btn primary" href="{{ route('roles.create') }}">+ Buat kategori</a>
</div>@endpermission<div class="card scroll">
<table>
<thead>
<tr>
<th>Kategori</th>
<th>Keterangan</th>
<th>Pengguna</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>@foreach($roles as $r)<tr>
<td>
<b>{{ $r->name }}</b>
</td>
<td>{{ $r->description }}</td>
<td>{{ $r->users_count }}</td>
<td>{{ $r->is_active?'Aktif':'Nonaktif' }}</td>
<td>
<div class="actions">@permission('permissions.view')<a class="btn" href="{{ route('permissions.index',['role_id'=>$r->id]) }}">Hak akses</a>@endpermission @if(!$r->is_system)@permission('roles.update')<a class="btn" href="{{ route('roles.edit',$r) }}">Ubah</a>@endpermission @permission('roles.delete')<form method="post" action="{{ route('roles.destroy',$r) }}" onsubmit="return confirm('Hapus kategori ini?')">@csrf @method('DELETE')<button class="danger">Hapus</button>
</form>@endpermission @else<small>Dilindungi</small>@endif</div>
</td>
</tr>@endforeach</tbody>
</table>
</div>@endsection
