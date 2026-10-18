@extends('guest.layout')
@section('title','Pesan Meja '.$table->number)
@section('content')
@foreach($recent as $o)<a class="guest-recent" href="{{ route('qr.order',[$table->token,$o->checkout_token]) }}">Pesanan Anda {{ $o->invoice_number }} · Rp {{ number_format($o->total,0,',','.') }} <b>Lihat status</b></a>@endforeach
<div class="guest-cats" id="cats"><button type="button" data-cat="" aria-pressed="true">Semua</button>@foreach($categories as $c)<button type="button" data-cat="{{ $c->id }}" aria-pressed="false">{{ $c->name }}</button>@endforeach</div>
<div id="menu"></div>
<form method="post" action="{{ route('qr.store',$table->token) }}" id="order-form" class="guest-cart" hidden>@csrf
<input type="hidden" name="checkout_token" value="{{ old('checkout_token',$checkoutToken) }}">
<h3>Pesanan Meja {{ $table->number }}</h3><div id="cart"></div><div id="items"></div>
<label class="field">Nama pemesan (opsional)<input name="guest_name" maxlength="100" value="{{ old('guest_name') }}" autocomplete="given-name"></label>
<div class="line total"><span>Total</span><b id="total"></b></div>
<fieldset class="guest-pay"><legend>Cara bayar</legend>
@if($qrisEnabled)<label class="paybox"><input type="radio" name="qr_payment" value="qris" @checked(old('qr_payment','qris')==='qris')><span><b>Bayar sekarang pakai QRIS</b><small>Pesanan disiapkan setelah kasir menerima dana.</small></span></label>@endif
<label class="paybox"><input type="radio" name="qr_payment" value="cashier" @checked(! $qrisEnabled || old('qr_payment')==='cashier')><span><b>Bayar di kasir</b><small>Tunai, debit, atau QRIS di kasir. Pesanan disiapkan setelah dikonfirmasi kasir.</small></span></label>
</fieldset>
<button class="primary guest-submit" id="submit">Kirim pesanan</button>
<button type="button" class="guest-back" id="back">Kembali ke menu</button>
</form>
<div class="guest-bar" id="bar" hidden><button type="button" class="primary" id="open-cart"></button></div>
@endsection
@push('scripts')
<script>const products={{ Illuminate\Support\Js::from($productData) }};const oldItems={{ Illuminate\Support\Js::from(old('items',[])) }};</script>
<script src="{{ asset('js/guest.js').'?v='.filemtime(public_path('js/guest.js')) }}" defer></script>
@endpush
