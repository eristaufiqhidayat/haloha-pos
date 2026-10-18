@extends('layouts.app')
@section('title','Pesanan Berjalan')
@section('content')
@permission('pos.create')<div class="toolbar"><a class="btn primary" href="{{ route('pos.index') }}">Transaksi Baru</a></div>@endpermission
@if($incoming->isNotEmpty())<h3>Pesanan dari QR meja</h3><div class="grid qr-incoming">@foreach($incoming as $o)@php($qris = $o->qr_payment === 'qris')<article class="card {{ ! $qris || $o->qris_claimed_at ? 'qr-attention' : '' }}"><div class="sectiontitle"><h3>Meja {{ $o->table_number }}</h3><span><span class="badge info">QR · {{ $qris ? 'QRIS' : 'Bayar di kasir' }}</span> <span class="badge warn">{{ $qris ? ($o->qris_claimed_at ? 'Cek dana masuk' : 'Menunggu tamu bayar') : 'Perlu konfirmasi' }}</span></span></div>
<p><b>{{ $o->guest_name ?: 'Tamu' }}</b></p><p class="muted">{{ $o->invoice_number }} · masuk {{ $o->created_at->format('H:i') }}@if($o->qris_claimed_at)<br>Tamu menekan "Saya sudah bayar" {{ $o->qris_claimed_at->format('H:i') }}@endif</p>
@foreach($o->kitchenItems([]) as $i)<div style="margin:12px 0"><b>{{ $i->quantity }} × {{ $i->product_name }}</b>@if($i->notes)<p class="muted item-notes">{{ $i->notes }}</p>@endif</div>@endforeach
@if($qris)<div class="line"><span>Nominal QRIS</span><b>Rp {{ number_format($o->qrisAmount(),0,',','.') }}</b></div><p class="muted">Total Rp {{ number_format($o->total,0,',','.') }} + kode unik Rp {{ $o->qris_code }}. Cocokkan dengan notifikasi QRIS di aplikasi bank sebelum memproses.</p>@else<p><b>Rp {{ number_format($o->total,0,',','.') }}</b></p>@endif
@permission('pos.create')<div class="actions"><form method="post" action="{{ route('orders.reject',$o) }}" onsubmit="return confirm('Tolak pesanan Meja {{ $o->table_number }}?')">@csrf<button class="danger">Tolak</button></form>
@if($qris)<form method="post" action="{{ route('orders.accept',$o) }}">@csrf<input type="hidden" name="as" value="cashier"><button>Bayar di kasir saja</button></form><form method="post" action="{{ route('orders.accept',$o) }}" onsubmit="return confirm('Dana QRIS Rp {{ number_format($o->qrisAmount(),0,',','.') }} sudah masuk?')">@csrf<input type="hidden" name="as" value="qris"><button class="primary">Dana masuk, proses</button></form>
@else<form method="post" action="{{ route('orders.accept',$o) }}">@csrf<input type="hidden" name="as" value="cashier"><button class="primary">Konfirmasi</button></form>@endif</div>@endpermission</article>@endforeach</div><h3>Pesanan berjalan</h3>@endif
<div class="grid">@forelse($orders as $o)<article class="card"><div class="sectiontitle"><h3>{{ $o->takeaway ? 'Take away' : 'Meja '.$o->table_number }}</h3><span>@if($o->source==='qr')<span class="badge info">QR</span> @endif<span class="badge warn">{{ $o->isSplit() ? 'Split '.$o->bills->count().' tagihan' : 'Berjalan' }}</span></span></div><p><b>{{ $o->guest_name ?: 'Tamu' }}</b>@if($o->isMixedTakeaway()) <span class="badge takeaway">{{ $o->takeawayQuantity() }} porsi take away</span>@endif</p><p class="muted">{{ $o->invoice_number }}<br>Kasir {{ $o->cashier_name ?? $o->user->name }} · {{ $o->created_at->format('d/m/Y H:i') }}</p>
@foreach($o->kitchenItems([]) as $i)<div style="margin:12px 0"><b>{{ $i->quantity }} × {{ $i->product_name }}</b>@if($i->takeaway && ! $o->takeaway) <span class="badge takeaway">TA</span>@endif @if($i->notes)<p class="muted item-notes">{{ $i->notes }}</p>@endif</div>@endforeach
<p><b>Rp {{ number_format($o->total,0,',','.') }}</b></p>
@if($o->isSplit())@php($paid=$o->bills->filter->isPaid())<div class="line"><span>Lunas {{ $paid->count() }} dari {{ $o->bills->count() }} tagihan</span><b>Rp {{ number_format($paid->sum('total'),0,',','.') }}</b></div><div class="bill-progress" aria-hidden="true"><i style="width:{{ $o->total ? round($paid->sum('total')/$o->total*100,1) : 0 }}%"></i></div>@endif
<div class="actions">
@foreach(config('pos.kitchen_stations') as $key => $st)
@if($o->kitchenItems($st)->isNotEmpty())<a class="btn" href="{{ route('orders.kitchen',[$o,$key]) }}">Cetak {{ mb_strtolower($st['label']) }}</a>@endif
@endforeach
@permission('pos.create')@if($o->isSplit())@unless($paid->count())<a class="btn" href="{{ route('orders.edit',$o) }}">Ubah / Notes</a><a class="btn" href="{{ route('orders.split',$o) }}">Ubah pembagian</a>@endunless<a class="btn primary" href="{{ route('orders.bills',$o) }}">Lihat tagihan</a>
@else<a class="btn" href="{{ route('orders.edit',$o) }}">Ubah / Notes</a><a class="btn" href="{{ route('orders.split',$o) }}">Split bill</a><a class="btn primary" href="{{ route('orders.payment',$o) }}">Bayar</a>@endif @endpermission</div></article>@empty<div class="card">Belum ada pesanan berjalan.</div>@endforelse</div>
@endsection
@push('scripts')
<script>
// Pesanan dari QR meja masuk tanpa kasir menyentuh layar: muat ulang berkala selama kasir tidak sedang mengetik.
setInterval(()=>{if(!document.hidden&&!document.activeElement?.matches('input,textarea,select'))location.reload()},30000);
</script>
@endpush
