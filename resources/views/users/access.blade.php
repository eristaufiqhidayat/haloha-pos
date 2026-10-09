@extends('layouts.app')
@section('title','Akses per Pengguna')
@section('content')
<div class="card form"><h3>{{ $user->name }} · {{ $user->role->name }}</h3>
@if($user->role->is_system)<p>Administrator mempertahankan seluruh akses fitur dan produk.</p><a class="btn" href="{{ route('users.index') }}">Kembali</a>
@else
<form method="post" action="{{ route('users.access.update',$user) }}">@csrf @method('PUT')
<h3>Akses fitur / sidebar</h3><input type="hidden" name="inherit_permissions" value="0"><label class="inline-check"><input class="checkbox" type="checkbox" name="inherit_permissions" id="inherit-permissions" value="1" @checked(old('inherit_permissions',$user->permission_codes===null))> Ikuti hak akses kategori pengguna</label>
<div class="grid" id="feature-choices">@foreach(config('pos.modules') as $module=>$config)<div class="card"><b>{{ $config['label'] }}</b>@foreach($config['actions'] as $action=>$label)@php($code=$module.'.'.$action)<label class="inline-check"><input class="checkbox feature-permission" type="checkbox" name="permission_codes[]" value="{{ $code }}" @checked(in_array($code,old('permission_codes',$user->permission_codes ?? $user->role->permissions->pluck('code')->all())))>{{ $label }}</label>@endforeach</div>@endforeach</div>
<h3>Akses menu yang boleh dijual</h3><p class="muted">Hanya produk yang dipilih muncul pada kasir ini. Produk baru perlu diberikan akses oleh admin.</p><div class="toolbar"><button type="button" onclick="document.querySelectorAll('.product-access').forEach(el=>el.checked=true)">Pilih semua</button><button type="button" onclick="document.querySelectorAll('.product-access').forEach(el=>el.checked=false)">Kosongkan</button></div>
<div class="grid">@foreach($products as $p)<label class="access-product"><input class="checkbox product-access" type="checkbox" name="product_ids[]" value="{{ $p->id }}" @checked(in_array($p->id,old('product_ids',$user->product_ids ?? $products->pluck('id')->all())))><span><b>{{ $p->name }}</b><br><small>{{ $p->category->name }} · {{ $p->sku }}{{ $p->is_active?'':' · Nonaktif' }}</small></span></label>@endforeach</div>
<div class="toolbar" style="margin-top:24px"><button class="primary">Simpan akses</button><a class="btn" href="{{ route('users.index') }}">Batal</a></div></form>
@endif</div>
@endsection
@push('scripts')<script>const inherit=document.getElementById('inherit-permissions');if(inherit){const update=()=>document.querySelectorAll('.feature-permission').forEach(el=>el.disabled=inherit.checked);inherit.onchange=update;update();}</script>@endpush
