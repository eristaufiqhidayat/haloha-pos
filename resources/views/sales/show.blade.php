@extends('layouts.app')
@section('title','Struk Penjualan')
@section('content')<div class="card receipt">
<img class="receipt-logo" src="{{ asset('images/haloha-logo.jpeg') }}" alt="Haloha — Halal, Original, Happiness" width="447" height="447">
<p>{{ $sale->invoice_number }}<br>{{ $sale->sold_at->format('d/m/Y H:i:s') }} · Kasir {{ $sale->user->name }}</p>
<div class="scroll">
<table>
<thead>
<tr>
<th>Produk</th>
<th>Qty</th>
<th>Harga</th>
<th>Jumlah</th>
</tr>
</thead>
<tbody>@foreach($sale->items as $i)<tr>
<td>{{ $i->product_name }}</td>
<td>{{ $i->quantity }}</td>
<td>{{ number_format($i->unit_price,0,',','.') }}</td>
<td>{{ number_format($i->subtotal,0,',','.') }}</td>
</tr>@endforeach</tbody>
</table>
</div>
<div class="line">
<span>Subtotal</span>
<b>Rp {{ number_format($sale->subtotal,0,',','.') }}</b>
</div>
<div class="line">
<span>Diskon</span>
<b>Rp {{ number_format($sale->discount,0,',','.') }}</b>
</div>
<div class="line total">
<span>Total</span>
<span>Rp {{ number_format($sale->total,0,',','.') }}</span>
</div>
<div class="line">
<span>{{ ['cash'=>'Tunai','qris'=>'QRIS','transfer'=>'Transfer'][$sale->payment_method] }}</span>
<b>Rp {{ number_format($sale->paid_amount,0,',','.') }}</b>
</div>
<div class="line">
<span>Kembalian</span>
<b>Rp {{ number_format($sale->change_amount,0,',','.') }}</b>
</div>
<p>Terima kasih atas kunjungan Anda.</p>
<div class="toolbar no-print">
<button class="primary" onclick="window.print()">Cetak struk</button>@permission('pos.view')<a class="btn" href="{{ route('pos.index') }}">Transaksi baru</a>@endpermission @permission('sales.view')<a class="btn" href="{{ route('reports.sales') }}">Laporan</a>@endpermission</div>
</div>@endsection
