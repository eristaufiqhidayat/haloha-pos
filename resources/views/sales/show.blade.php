@extends('layouts.app')
@section('title','Struk Penjualan')
@section('content')
<div class="card thermal-receipt">@include('sales.header',['kitchen'=>false])
@foreach($sale->items as $i)<div style="margin:10px 0"><b>{{ $i->product_name }}</b><div class="line"><span>{{ $i->quantity }} × {{ number_format($i->unit_price,0,',','.') }}</span><span>{{ number_format($i->subtotal,0,',','.') }}</span></div>@if($i->notes)<div class="item-notes">Catatan: {{ $i->notes }}</div>@endif</div>@endforeach<hr>
<div class="line"><span>Subtotal</span><b>Rp {{ number_format($sale->subtotal,0,',','.') }}</b></div>
@if($sale->discount_enabled)<div class="line"><span>Diskon</span><b>Rp {{ number_format($sale->discount,0,',','.') }}</b></div>@endif
@if($sale->tax_enabled)<div class="line"><span>Pajak {{ $sale->tax_rate }}%</span><b>Rp {{ number_format($sale->tax_amount,0,',','.') }}</b></div>@endif
<div class="line total"><span>Total</span><b>Rp {{ number_format($sale->total,0,',','.') }}</b></div><hr>
@foreach($sale->payments as $p)<div class="line"><span>{{ ['cash'=>'Tunai','qris'=>'QRIS','transfer'=>'Transfer','debit'=>'Debit'][$p->method] }}</span><span>Rp {{ number_format($p->amount,0,',','.') }}</span></div>@if($p->method==='cash')<div class="line"><span>Tunai diterima</span><span>Rp {{ number_format($p->paid_amount,0,',','.') }}</span></div><div class="line"><span>Kembalian</span><span>Rp {{ number_format($p->change_amount,0,',','.') }}</span></div>@endif @endforeach<hr><p style="text-align:center">Terima kasih atas kedatangannya.</p></div>
<div class="toolbar no-print receipt-actions"><button class="primary" onclick="window.print()">Cetak struk</button>@permission('pos.view')<a class="btn" href="{{ route('pos.index') }}">Transaksi baru</a><a class="btn" href="{{ route('orders.index') }}">Pesanan berjalan</a>@endpermission @permission('sales.view')<a class="btn" href="{{ route('reports.sales') }}">Laporan</a>@endpermission</div>
@endsection
