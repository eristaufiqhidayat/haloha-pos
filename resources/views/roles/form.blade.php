@extends('layouts.app')
@section('title',$role->exists?'Ubah Kategori Pengguna':'Kategori Pengguna Baru')
@section('content')<div class="card">
<form class="form" method="post" action="{{ $role->exists?route('roles.update',$role):route('roles.store') }}">@csrf @if($role->exists)@method('PUT')@endif<label>Nama kategori<input name="name" required maxlength="80" value="{{ old('name',$role->name) }}">
</label>
<label>Keterangan<textarea name="description" maxlength="500">{{ old('description',$role->description) }}</textarea>
</label>
<label>Status<select name="is_active">
<option value="1" @selected(old('is_active',$role->is_active)==1)>Aktif</option>
<option value="0" @selected(old('is_active',$role->is_active)==0)>Nonaktif</option>
</select>
</label>
<div class="toolbar">
<button class="primary">Simpan</button>
<a class="btn" href="{{ route('roles.index') }}">Batal</a>
</div>
</form>
</div>@endsection
