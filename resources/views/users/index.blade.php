@extends('layouts.app')
@section('title','Pengguna')
@section('content')@permission('users.create')<div class="toolbar">
<a class="btn primary" href="{{ route('users.create') }}">+ Buat pengguna</a>
</div>@endpermission<div class="card scroll">
<table>
<thead>
<tr>
<th>Nama</th>
<th>Email</th>
<th>Kategori</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>@forelse($users as $u)<tr>
<td>{{ $u->name }}</td>
<td>{{ $u->email }}</td>
<td>{{ $u->role?->name??'Belum ditentukan' }}</td>
<td>
<span class="badge {{ !$u->is_active?'warn':'' }}">{{ $u->is_active?'Aktif':'Nonaktif' }}</span>
</td>
<td>
<div class="actions">@if(auth()->user()->role->is_system)<a class="btn" href="{{ route('users.access',$u) }}">Atur akses</a>@endif @permission('users.update')<a class="btn" href="{{ route('users.edit',$u) }}">Ubah</a>@endpermission @permission('users.delete')@if($u->id!==auth()->id())<form method="post" action="{{ route('users.destroy',$u) }}" onsubmit="return confirm('Hapus pengguna ini?')">@csrf @method('DELETE')<button class="danger">Hapus</button>
</form>@endif
@endpermission</div>
</td>
</tr>@empty<tr>
<td colspan="5">Belum ada pengguna.</td>
</tr>@endforelse</tbody>
</table>
<div class="pagination">{{ $users->links('pagination::simple-default') }}</div>
</div>@endsection
