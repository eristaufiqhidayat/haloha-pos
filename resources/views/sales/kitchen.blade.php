@extends('layouts.app')
@section('title','Pesanan Dapur')
@section('content')
<div class="card thermal-receipt">@include('sales.header',['kitchen'=>true])
@foreach($sale->items as $i)<div style="margin:14px 0"><b>{{ $i->quantity }} × {{ $i->product_name }}</b>@if($i->notes)<div class="item-notes">Catatan: {{ $i->notes }}</div>@endif</div>@endforeach</div>
<div class="toolbar no-print receipt-actions"><button class="primary" onclick="window.print()">Cetak dapur</button><a class="btn" href="{{ route('orders.index') }}">Pesanan berjalan</a></div>
@endsection
