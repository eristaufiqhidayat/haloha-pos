@extends('layouts.app')
@section('title',$product->exists?'Ubah Produk':'Produk Baru')
@section('content')<div class="card">
<form class="form" method="post" action="{{ $product->exists?route('products.update',$product):route('products.store') }}">@csrf @if($product->exists)@method('PUT')@endif<div class="grid">
<label>Nama produk<input name="name" required maxlength="150" value="{{ old('name',$product->name) }}">
</label>
<label>SKU<input name="sku" required maxlength="60" value="{{ old('sku',$product->sku) }}">
</label>
<label>Kategori<select name="category_id" required>
<option value="">Pilih kategori</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id',$product->category_id)==$c->id)>{{ $c->name }}</option>@endforeach</select>
</label>
<label>Status<select name="is_active">
<option value="1" @selected(old('is_active',$product->is_active)==1)>Aktif</option>
<option value="0" @selected(old('is_active',$product->is_active)==0)>Arsip</option>
</select>
</label>
<label>Harga beli / HPP (Rp)<input type="number" name="cost_price" required min="0" max="1000000000" value="{{ old('cost_price',$product->cost_price) }}">
</label>
<label>Harga jual (Rp)<input type="number" name="selling_price" required min="1" max="1000000000" value="{{ old('selling_price',$product->selling_price) }}">
</label>
<label>Batas minimum stok<input type="number" name="minimum_stock" required min="0" value="{{ old('minimum_stock',$product->minimum_stock) }}">
</label>
</div>
<p class="muted">Stok dicatat melalui menu Stok masuk. Harga beli disalin sebagai HPP saat transaksi; perubahan harga tidak mengubah riwayat.</p>
<div class="toolbar" style="margin-top:20px">
<button class="primary">Simpan</button>
<a class="btn" href="{{ route('products.index') }}">Batal</a>
</div>
</form>
</div>@endsection
