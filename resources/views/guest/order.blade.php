@extends('guest.layout')
@section('title','Pesanan '.$sale->invoice_number)
@php($waiting = $sale->status === 'incoming' && ($sale->qr_payment === 'cashier' || $sale->qris_claimed_at))
@if($waiting)@push('head')<meta http-equiv="refresh" content="15">@endpush @endif
@section('content')
<section class="card guest-status">
@if($sale->status === 'incoming' && $sale->qr_payment === 'qris' && ! $sale->qris_claimed_at)
@if($qrSvg)<div class="qris-box"><div class="qris-logo">QRIS</div><small>{{ $merchant }}</small><div class="qris-code" id="qris-code">{!! $qrSvg !!}</div><small>Total yang dibayar</small><div class="qris-amount">Rp {{ number_format($sale->qrisAmount(),0,',','.') }}</div><small>termasuk kode unik Rp {{ $sale->qris_code }}</small></div>
<p class="muted center">Bayar sebelum {{ $sale->qrisExpiresAt()->format('H:i') }} · {{ $sale->invoice_number }}</p>
<button type="button" class="guest-back" id="save-qr">Simpan gambar QR</button>
<p class="muted">Bayar dari HP ini: simpan gambar, lalu pilih "scan dari galeri" di aplikasi bank atau e-wallet. Pastikan nominal sama persis.</p>
<form method="post" action="{{ route('qr.claim',[$table->token,$sale->checkout_token]) }}">@csrf<button class="primary guest-submit">Saya sudah bayar</button></form>
@else<h2>Bayar di kasir</h2><p class="muted">Pembayaran QRIS dari HP sedang tidak tersedia. Pesanan Anda tetap tercatat; silakan bayar ke kasir.</p>@endif
@elseif($sale->status === 'incoming')<div class="status-icon wait" aria-hidden="true">⏳</div><h2>{{ $sale->qr_payment === 'qris' ? 'Pembayaran sedang dicek' : 'Pesanan terkirim' }}</h2>
<p><span class="badge warn">{{ $sale->qr_payment === 'qris' ? 'Menunggu kasir cek dana masuk' : 'Menunggu konfirmasi kasir' }}</span></p><p class="muted">Halaman ini diperbarui otomatis.</p>
@elseif($sale->status === 'cancelled')<div class="status-icon stop" aria-hidden="true">✕</div><h2>Pesanan dibatalkan</h2><p class="muted">Pesanan ditolak kasir atau batas waktu pembayaran habis. Silakan pesan lagi atau hubungi kasir.</p>
@else<div class="status-icon ok" aria-hidden="true">✓</div><h2>Pesanan sedang disiapkan</h2><p><span class="badge">{{ $sale->status === 'paid' ? 'Lunas' : 'Bayar di kasir' }}</span></p>@endif
</section>
<section class="card"><h3>{{ $sale->invoice_number }}</h3>
@foreach($sale->items as $i)<div class="line"><span>{{ $i->quantity }} × {{ $i->product_name }}@if($i->notes)<br><small class="muted">{{ $i->notes }}</small>@endif</span><span>Rp {{ number_format($i->subtotal,0,',','.') }}</span></div>@endforeach
<div class="line total"><span>Total</span><b>Rp {{ number_format($sale->total,0,',','.') }}</b></div></section>
<a class="btn guest-back" href="{{ route('qr.menu',$table->token) }}">Tambah pesanan lagi</a>
@endsection
@push('scripts')
<script>
// Simpan QR sebagai PNG supaya bisa dibuka lewat "scan dari galeri" di aplikasi bank.
document.getElementById('save-qr')?.addEventListener('click',()=>{const svg=document.querySelector('#qris-code svg'),img=new Image();img.onload=()=>{const c=document.createElement('canvas');c.width=c.height=720;const x=c.getContext('2d');x.fillStyle='#fff';x.fillRect(0,0,720,720);x.drawImage(img,40,40,640,640);const a=document.createElement('a');a.download='QRIS-{{ $sale->invoice_number }}.png';a.href=c.toDataURL('image/png');a.click()};img.src='data:image/svg+xml;charset=utf-8,'+encodeURIComponent(new XMLSerializer().serializeToString(svg))});
</script>
@endpush
