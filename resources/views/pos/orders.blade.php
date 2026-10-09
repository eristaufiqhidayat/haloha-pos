@extends('layouts.app')
@section('title','Pesanan Berjalan')
@section('content')
@permission('pos.create')<div class="toolbar"><a class="btn primary" href="{{ route('pos.index') }}">Transaksi Baru</a></div>@endpermission
<div class="grid">@forelse($orders as $o)<article class="card"><div class="sectiontitle"><h3>{{ $o->takeaway ? 'Take away' : 'Meja '.$o->table_number }}</h3><span class="badge warn">Berjalan</span></div><p><b>{{ $o->guest_name ?: 'Tamu' }}</b></p><p class="muted">{{ $o->invoice_number }}<br>Kasir {{ $o->cashier_name ?? $o->user->name }} · {{ $o->created_at->format('d/m/Y H:i') }}</p>
@foreach($o->items as $i)<div style="margin:12px 0"><b>{{ $i->quantity }} × {{ $i->product_name }}</b>@if($i->notes)<p class="muted item-notes">{{ $i->notes }}</p>@endif</div>@endforeach
<p><b>Rp {{ number_format($o->total,0,',','.') }}</b></p><div class="actions"><a class="btn" href="{{ route('orders.kitchen',$o) }}">Cetak dapur</a>@permission('pos.create')<a class="btn" href="{{ route('orders.edit',$o) }}">Ubah / Notes</a><a class="btn primary" href="{{ route('orders.payment',$o) }}">Bayar</a>@endpermission</div></article>@empty<div class="card">Belum ada pesanan berjalan.</div>@endforelse</div>
@endsection
