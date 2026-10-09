<div class="scroll">
<table>
<thead>
<tr>
<th>Invoice / waktu</th>
<th>Kasir</th>
<th>Unit</th>
<th>Pembayaran</th>
<th>Diskon</th>
<th>Total</th>
<th>
</th>
</tr>
</thead>
<tbody>@forelse($report['rows'] as $sale)<tr>
<td>
<b>{{ $sale->invoice_number }}</b>
<br>
<small>{{ $sale->sold_at->format('H:i') }}</small>
</td>
<td>{{ $sale->cashier_name ?? $sale->user->name }}</td>
<td>{{ $sale->items->sum('quantity') }}</td>
<td>{{ ['cash'=>'Tunai','qris'=>'QRIS','transfer'=>'Transfer','debit'=>'Debit','split'=>'Split'][$sale->payment_method] }}</td>
<td>Rp {{ number_format($sale->discount,0,',','.') }}</td>
<td>
<b>Rp {{ number_format($sale->total,0,',','.') }}</b>
</td>
<td>@permission('sales.view')<a class="btn" href="{{ route('sales.show',$sale) }}">Struk</a>@endpermission</td>
</tr>@empty<tr>
<td colspan="7">Belum ada transaksi pada tanggal ini.</td>
</tr>@endforelse</tbody>
</table>
</div>
