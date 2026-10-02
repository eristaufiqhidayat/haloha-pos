@extends('layouts.app')
@section('title',$user->exists?'Ubah Pengguna':'Buat Pengguna')
@section('content')<div class="card">
<form class="form" method="post" action="{{ $user->exists?route('users.update',$user):route('users.store') }}">@csrf @if($user->exists)@method('PUT')@endif<div class="grid">
<label>Nama<input name="name" required maxlength="100" value="{{ old('name',$user->name) }}">
</label>
<label>Email<input type="email" name="email" required maxlength="150" value="{{ old('email',$user->email) }}">
</label>
<label>Kategori pengguna<select name="role_id" required>
<option value="">Pilih kategori</option>@foreach($roles as $r)<option value="{{ $r->id }}" @selected(old('role_id',$user->role_id)==$r->id)>{{ $r->name }}</option>@endforeach</select>
</label>
<label>Status<select name="is_active">
<option value="1" @selected(old('is_active',$user->is_active)==1)>Aktif</option>
<option value="0" @selected(old('is_active',$user->is_active)==0)>Nonaktif</option>
</select>
</label>
<label>Password {{ $user->exists?'(kosongkan bila tetap)':'' }}<input name="password" type="password" minlength="8" maxlength="128" autocomplete="new-password" @required(!$user->exists)>
</label>
<label>Konfirmasi password<input name="password_confirmation" type="password" minlength="8" maxlength="128" autocomplete="new-password" @required(!$user->exists)>
</label>
</div>
<div class="toolbar">
<button class="primary">Simpan</button>
<a class="btn" href="{{ route('users.index') }}">Batal</a>
</div>
</form>
</div>@endsection
