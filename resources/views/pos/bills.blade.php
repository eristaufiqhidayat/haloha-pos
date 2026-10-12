@extends('layouts.app')
@section('title','Tagihan '.($order->takeaway ? 'Take away' : 'Meja '.$order->table_number))
@section('content')
@php($paid=$order->bills->filter->isPaid())
<div class="card"><div class="sectiontitle"><div><h3>{{ $order->guest_name ?: 'Tamu' }} · {{ $order->invoice_number }}</h3><p class="muted">{{ $order->split_mode==='equal' ? 'Bagi rata' : 'Per item' }} · Kasir {{ $order->cashier_name }}</p></div><span class="badge {{ $order->status==='paid' ? '' : 'warn' }}">{{ $order->status==='paid' ? 'Lunas semua' : 'Lunas '.$paid->count().' dari '.$order->bills->count() }}</span></div>
<div class="line"><span>Terbayar {{ $paid->count() }} dari {{ $order->bills->count() }} tagihan</span><b>Rp {{ number_format($paid->sum('total'),0,',','.') }} / Rp {{ number_format($order->total,0,',','.') }}</b></div>
<div class="bill-progress" aria-hidden="true"><i style="width:{{ $order->total ? round($paid->sum('total')/$order->total*100,1) : 0 }}%"></i></div>
<div class="scroll" style="margin-top:16px"><table><thead><tr><th>Tagihan</th><th>Isi</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($order->bills as $b)<tr><td><b>T{{ $b->number }}</b>@if($b->label) {{ $b->label }}@endif</td>
<td style="white-space:normal;min-width:200px">@if($order->split_mode==='equal')Bagian {{ $b->number }} dari {{ $order->bills->count() }}@else{{ $b->items->map(fn($i)=>$i->quantity.'× '.$i->product_name)->join(', ') }}@endif</td>
<td><b>Rp {{ number_format($b->total,0,',','.') }}</b></td>
<td>@if($b->isPaid())<span class="badge">Lunas · {{ $b->payments->map(fn($p)=>['cash'=>'Tunai','qris'=>'QRIS','transfer'=>'Transfer','debit'=>'Debit'][$p->method])->join(' + ') }}</span>@else<span class="badge warn">Belum bayar</span>@endif</td>
<td>@if($b->isPaid())<a class="btn" href="{{ route('sales.bill',[$order,$b->number]) }}">Cetak struk</a>@else @permission('pos.create')<a class="btn primary" href="{{ route('orders.bills.payment',[$order,$b->number]) }}">Bayar</a>@endpermission @endif</td></tr>@endforeach
</tbody></table></div>
<p class="muted">Pembagian terkunci setelah ada tagihan lunas. Stok dipotong saat tagihan pertama lunas; pesanan masuk laporan penjualan saat tagihan terakhir lunas.</p>
<div class="actions">@if($order->status==='pending' && ! $paid->count())@permission('pos.create')<a class="btn" href="{{ route('orders.split',$order) }}">Ubah pembagian</a>@endpermission @endif
@if($order->status==='paid')<a class="btn" href="{{ route('sales.show',$order) }}">Struk gabungan</a>@endif<a class="btn" href="{{ route('orders.index') }}">Pesanan berjalan</a></div></div>
@endsection
