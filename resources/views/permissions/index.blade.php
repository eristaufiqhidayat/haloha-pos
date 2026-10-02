@extends('layouts.app')
@section('title','Hak Akses Kategori Pengguna')
@section('content')<form method="get" class="toolbar">
<label>Kategori <select name="role_id" onchange="this.form.submit()">@foreach($roles as $r)<option value="{{ $r->id }}" @selected($role->id===$r->id)>{{ $r->name }}</option>@endforeach</select>
</label>
<button>Tampilkan</button>
</form>
<div class="card">
<form method="post" action="{{ route('permissions.update',$role) }}">@csrf @method('PUT')<div class="scroll">
<table>
<thead>
<tr>
<th>Modul</th>
<th>Lihat</th>
<th>Tambah / Transaksi</th>
<th>Ubah</th>
<th>Hapus / Arsipkan</th>
<th>Ekspor</th>
</tr>
</thead>
<tbody>@foreach(config('pos.modules') as $module=>$def)<tr>
<td>{{ $def['label'] }}</td>@foreach(['view','create','update','delete','export'] as $action)<td>@if(isset($def['actions'][$action]))<input class="checkbox" type="checkbox" name="permissions[]" value="{{ $module.'.'.$action }}" aria-label="{{ $def['label'].' '.$def['actions'][$action] }}" @checked($role->is_system || in_array($module.'.'.$action,old('permissions',$role->permissions->pluck('code')->all()))) @disabled($role->is_system || !auth()->user()->hasPermission('permissions.update'))>@else<span class="muted">—</span>@endif</td>@endforeach</tr>@endforeach</tbody>
</table>
</div>@if($role->is_system)<p class="muted">Administrator memiliki seluruh izin dan tidak dapat diubah.</p>@else<p class="muted">Izin tindakan otomatis menyertakan izin melihat modul terkait.</p>@permission('permissions.update')<button class="primary">Simpan hak akses</button>@endpermission
@endif</form>
</div>@endsection
