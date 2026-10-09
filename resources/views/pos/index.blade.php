@extends('layouts.app')
@section('title',$order ? 'Ubah Pesanan' : 'Transaksi Baru')
@section('content')
<p class="muted">Kasir: <b>{{ $order?->cashier_name ?? auth()->user()->name }}</b> · Menu sesuai akses yang diberikan admin.</p>
<div class="split">
<section><div class="toolbar"><input id="search" style="flex:1" placeholder="Cari produk atau SKU" aria-label="Cari produk"><select id="category" aria-label="Kategori"><option value="">Semua kategori</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div><div class="products" id="products"></div></section>
<section class="card form"><div class="sectiontitle"><h3>Pesanan</h3><button type="button" id="clear">Kosongkan</button></div>
<form method="post" action="{{ $order ? route('orders.update',$order) : route('pos.store') }}" id="checkout">@csrf @if($order)@method('PUT')@endif
<input type="hidden" name="action" value="pending"><input type="hidden" name="checkout_token" value="{{ old('checkout_token',$checkoutToken) }}">
<div class="grid"><label>Nama tamu<input name="guest_name" maxlength="100" value="{{ old('guest_name',$order?->guest_name) }}" placeholder="Nama tamu"></label><label>Nomor meja<input name="table_number" id="table-number" maxlength="30" value="{{ old('table_number',$order?->table_number) }}" placeholder="01"></label></div>
<input type="hidden" name="takeaway" value="0"><label class="inline-check"><input class="checkbox" name="takeaway" id="takeaway" type="checkbox" value="1" @checked(old('takeaway',$order?->takeaway))> Take away</label>
<div id="cart"></div><div id="items"></div>
<div class="line"><span>Subtotal</span><b id="subtotal"></b></div>
<input type="hidden" name="discount_enabled" value="0"><label class="inline-check"><input class="checkbox" name="discount_enabled" id="discount-enabled" type="checkbox" value="1" @checked(old('discount_enabled',$order?->discount_enabled))> Gunakan diskon</label>
<label>Diskon (Rp)<input type="number" name="discount" id="discount" min="0" max="1000000000000" value="{{ old('discount',$order?->discount ?? 0) }}"></label>
<input type="hidden" name="tax_enabled" value="0"><label class="inline-check"><input class="checkbox" name="tax_enabled" id="tax-enabled" type="checkbox" value="1" @checked(old('tax_enabled',$order?->tax_enabled))> Gunakan pajak</label>
<label>Pajak (%)<input type="number" name="tax_rate" id="tax-rate" min="0" max="100" step="1" value="{{ old('tax_rate',$order?->tax_rate ?? 10) }}"></label><div class="line" id="tax-line"><span>Pajak</span><b id="tax-amount"></b></div>
<div class="line total"><span>Total</span><span id="total"></span></div><p class="muted" id="cart-error" role="status"></p>
@permission('pos.create')<button class="primary" id="pay" style="width:100%">{{ $order ? 'Simpan perubahan' : 'Simpan ke Pesanan Berjalan' }}</button>@endpermission
<p class="muted">Stok berkurang setelah pembayaran lunas. Diskon dan pajak hanya dicetak bila dipilih.</p></form></section></div>
<dialog id="notes-modal" class="pos-dialog"><form method="dialog"><div class="sectiontitle"><h3>Notes per menu</h3><button value="cancel">Tutup</button></div></form><p id="notes-menu"></p><label class="form">Catatan<textarea id="notes-text" rows="4" maxlength="600" style="width:100%" placeholder="Tidak pedas, tanpa bawang, sedikit es"></textarea></label><p class="muted">Catatan berlaku untuk jumlah pada baris menu ini.</p><button type="button" class="primary" id="notes-save">Simpan catatan</button></dialog>
@endsection
@push('scripts')
<script>const products={{ Illuminate\Support\Js::from($productData) }};const oldItems={{ Illuminate\Support\Js::from(old('items',$order ? $order->items->map(fn($i)=>['product_id'=>$i->product_id,'quantity'=>$i->quantity,'notes'=>$i->notes])->values()->all() : [])) }};const canTransact=@json(auth()->user()->hasPermission('pos.create'));</script>
<script src="{{ asset('js/pos.js').'?v='.filemtime(public_path('js/pos.js')) }}" defer></script>
@endpush
