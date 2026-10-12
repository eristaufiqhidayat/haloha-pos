@extends('layouts.app')
@section('title','Struk '.$bill->title())
@section('content')
<div class="card thermal-receipt">@include('sales.header',['kitchen'=>false,'billReceipt'=>$bill])
@foreach($sale->split_mode==='item' ? $bill->items : $sale->items()->get() as $i)<div style="margin:10px 0"><b>{{ $i->product_name }}</b><div class="line"><span>{{ $i->quantity }} × {{ number_format($i->unit_price,0,',','.') }}</span><span>{{ number_format($i->subtotal,0,',','.') }}</span></div>@if($i->notes)<div class="item-notes">Catatan: {{ $i->notes }}</div>@endif</div>@endforeach<hr>
@if($sale->split_mode==='equal')<div class="line"><span>Total pesanan</span><b>Rp {{ number_format($sale->total,0,',','.') }}</b></div><div class="line"><span>Bagi rata</span><span>Bagian {{ $bill->number }} dari {{ $sale->bills->count() }}</span></div>
@else<div class="line"><span>Subtotal</span><b>Rp {{ number_format($bill->subtotal,0,',','.') }}</b></div>
@if($sale->discount_enabled)<div class="line"><span>Diskon</span><b>Rp {{ number_format($bill->discount,0,',','.') }}</b></div>@endif
@if($sale->tax_enabled)<div class="line"><span>Pajak {{ $sale->tax_rate }}%</span><b>Rp {{ number_format($bill->tax_amount,0,',','.') }}</b></div>@endif @endif
<div class="line total"><span>Total tagihan</span><b>Rp {{ number_format($bill->total,0,',','.') }}</b></div><hr>
@foreach($bill->payments as $p)<div class="line"><span>{{ ['cash'=>'Tunai','qris'=>'QRIS','transfer'=>'Transfer','debit'=>'Debit'][$p->method] }}</span><span>Rp {{ number_format($p->amount,0,',','.') }}</span></div>@if($p->method==='cash')<div class="line"><span>Tunai diterima</span><span>Rp {{ number_format($p->paid_amount,0,',','.') }}</span></div><div class="line"><span>Kembalian</span><span>Rp {{ number_format($p->change_amount,0,',','.') }}</span></div>@endif @endforeach<hr><p style="text-align:center">Total pesanan: Rp {{ number_format($sale->total,0,',','.') }}<br>Terima kasih atas kedatangannya.</p></div>
<div class="toolbar no-print receipt-actions"><button class="primary" onclick="window.print()">Cetak struk</button>@permission('pos.view')<a class="btn" href="{{ route('orders.bills',$sale) }}">Daftar tagihan</a><a class="btn" href="{{ route('orders.index') }}">Pesanan berjalan</a>@endpermission</div>
@endsection
