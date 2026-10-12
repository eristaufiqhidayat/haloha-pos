@extends('layouts.app')
@section('title','Split Bill · '.($order->takeaway ? 'Take away' : 'Meja '.$order->table_number))
@section('content')
<form method="post" action="{{ route('orders.split.store',$order) }}" id="split-form">@csrf
<input type="hidden" name="mode" id="split-mode" value="{{ old('mode',$order->split_mode ?? 'item') }}"><input type="hidden" name="count" id="split-count-input">
<div class="card toolbar split-toolbar"><div class="seg" role="group" aria-label="Cara membagi"><button type="button" id="mode-item">Per item</button><button type="button" id="mode-equal">Bagi rata</button></div>
<div class="stepper"><span class="muted">Jumlah tagihan</span><button type="button" id="split-dec" aria-label="Kurangi tagihan">−</button><output id="split-count" aria-live="polite"></output><button type="button" id="split-inc" aria-label="Tambah tagihan">+</button><span class="muted">maks. {{ $max }}</span></div>
<span class="muted">{{ $order->guest_name ?: 'Tamu' }} · {{ $order->invoice_number }} · Total <b>Rp {{ number_format($order->total,0,',','.') }}</b></span></div>
<div class="split"><section class="card">
<div id="item-mode"><p class="muted">Pilih tagihan aktif, lalu ketuk kotak porsi untuk memasukkan porsi itu ke tagihan tersebut. Ketuk lagi untuk mengeluarkannya.</p><div class="chips" id="split-chips"></div><div id="split-items"></div></div>
<div id="equal-mode" hidden><h3>Bagi rata</h3><p class="muted">Total pesanan dibagi sama besar ke setiap tagihan. Selisih pembulatan rupiah masuk ke tagihan awal. Struk tiap tagihan mencantumkan seluruh item.</p>
@foreach($order->items->groupBy->splitKey() as $rows)<div class="line"><span>{{ $rows->sum('quantity') }} × {{ $rows->first()->product_name }}@if($rows->first()->takeaway && ! $order->takeaway) [TA]@endif</span><span>Rp {{ number_format($rows->sum('subtotal'),0,',','.') }}</span></div>@endforeach</div>
</section>
<section class="card split-summary"><h3>Ringkasan tagihan</h3><div id="split-status" role="status"></div><div class="bills" id="split-bills"></div>
<div class="actions"><a class="btn" href="{{ route('orders.index') }}">Batal</a><button class="primary" id="split-save">Simpan pembagian</button></div>
<p class="muted">Diskon dan pajak dibagi proporsional. Pembagian terkunci setelah ada tagihan lunas.</p></section></div>
<div id="split-hidden"></div></form>
@if($order->isSplit())<form method="post" action="{{ route('orders.split.destroy',$order) }}" class="toolbar" style="margin-top:12px">@csrf @method('DELETE')<button class="danger">Batalkan split, kembali ke satu tagihan</button></form>@endif
@endsection
@push('scripts')<script>const splitData={{ Illuminate\Support\Js::from([
    'items' => $items, 'mixed' => $order->isMixedTakeaway(), 'max' => $max, 'subtotal' => $order->subtotal, 'discount' => $order->discount, 'tax' => $order->tax_amount, 'total' => $order->total,
    'count' => (int) old('count', max(2, $order->bills->count())), 'labels' => old('labels', $order->bills->pluck('label', 'number')),
]) }};</script><script src="{{ asset('js/split.js').'?v='.filemtime(public_path('js/split.js')) }}" defer></script>@endpush
