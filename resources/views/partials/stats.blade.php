<div class="stats">@foreach([['Omzet bersih','Rp '.number_format($report['revenue'],0,',','.')],['Transaksi',$report['rows']->count()],['Produk terjual',$report['quantity'].' unit'],['Laba kotor','Rp '.number_format($report['profit'],0,',','.')]] as [$label,$value])<div class="card stat">
<span class="muted">{{ $label }}</span>
<strong>{{ $value }}</strong>
</div>@endforeach</div>
