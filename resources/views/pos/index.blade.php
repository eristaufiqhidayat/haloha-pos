@extends('layouts.app')
@section('title','Kasir / POS')
@section('content')
<div class="split">
<section>
<div class="toolbar">
<input id="search" style="flex:1" placeholder="Cari produk atau SKU" aria-label="Cari produk">
<select id="category" aria-label="Kategori">
<option value="">Semua kategori</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
</div>
<div class="products" id="products">
</div>
</section>
<section class="card form">
<div class="sectiontitle">
<h3>Keranjang</h3>
<button type="button" id="clear">Kosongkan</button>
</div>
<form method="post" action="{{ route('pos.store') }}" id="checkout">@csrf<input type="hidden" name="checkout_token" value="{{ old('checkout_token',$checkoutToken) }}">
<div id="cart">
</div>
<div id="items">
</div>
<div class="line">
<span>Subtotal</span>
<b id="subtotal">
</b>
</div>
<label>Diskon (Rp)<input type="number" name="discount" id="discount" min="0" max="1000000000000" value="{{ old('discount',0) }}">
</label>
<div class="line total">
<span>Total</span>
<span id="total">
</span>
</div>
<label>Metode pembayaran<select name="payment_method" id="method">@foreach(['cash'=>'Tunai','qris'=>'QRIS','transfer'=>'Transfer'] as $value=>$name)<option value="{{ $value }}" @selected(old('payment_method')===$value)>{{ $name }}</option>@endforeach</select>
</label>
<label id="paid-label">Uang diterima (Rp)<input type="number" name="paid_amount" id="paid" min="0" max="1000000000000" value="{{ old('paid_amount') }}">
</label>
<div class="line">
<span>Kembalian</span>
<b id="change">
</b>
</div>
<label style="display:flex;align-items:start">
<input style="width:20px;margin-top:5px" name="confirmed" type="checkbox" value="1" required> Pembayaran sudah diterima</label>
<p class="muted" id="cart-error" role="status">
</p>@permission('pos.create')<button class="primary" id="pay" style="width:100%">Bayar & simpan</button>@else<p class="muted">Anda hanya memiliki izin melihat halaman kasir.</p>@endpermission</form>
</section>
</div>
@endsection
@push('scripts')
<script>
const products={{ Illuminate\Support\Js::from($productData) }};
const oldItems={{ Illuminate\Support\Js::from(old('items',[])) }};const canTransact=@json(auth()->user()->hasPermission('pos.create'));
</script>
<script src="{{ asset('js/pos.js') }}" defer>
</script>
@endpush
