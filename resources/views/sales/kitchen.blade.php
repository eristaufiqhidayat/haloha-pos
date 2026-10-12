@extends('layouts.app')
@section('title','Pesanan '.$station['label'])
@section('content')
@if($items->isEmpty())<div class="card">Tidak ada menu untuk {{ $station['label'] }} pada pesanan ini.</div>
@else<div class="card thermal-receipt">@include('sales.header',['kitchen'=>true])
@foreach($items as $i)<div style="margin:14px 0"><b>{{ $i->quantity }} × {{ $i->product_name }}</b>@if($i->notes)<div class="item-notes">Catatan: {{ $i->notes }}</div>@endif</div>@endforeach</div>@endif
<div class="toolbar no-print receipt-actions">@unless($items->isEmpty())<button class="primary" onclick="window.print()">Cetak {{ mb_strtolower($station['label']) }}</button>@endunless<a class="btn" href="{{ route('orders.index') }}">Pesanan berjalan</a></div>
@endsection
