@extends('layouts.app')
@section('title','Pesanan Berjalan')
@section('content')
@permission('pos.create')<div class="toolbar"><a class="btn primary" href="{{ route('pos.index') }}">Transaksi Baru</a></div>@endpermission
<div class="grid">@forelse($orders as $o)<article class="card"><div class="sectiontitle"><h3>{{ $o->takeaway ? 'Take away' : 'Meja '.$o->table_number }}</h3><span class="badge warn">{{ $o->isSplit() ? 'Split '.$o->bills->count().' tagihan' : 'Berjalan' }}</span></div><p><b>{{ $o->guest_name ?: 'Tamu' }}</b>@if($o->isMixedTakeaway()) <span class="badge takeaway">{{ $o->takeawayQuantity() }} porsi take away</span>@endif</p><p class="muted">{{ $o->invoice_number }}<br>Kasir {{ $o->cashier_name ?? $o->user->name }} · {{ $o->created_at->format('d/m/Y H:i') }}</p>
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
