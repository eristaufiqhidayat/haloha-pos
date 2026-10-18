@extends('layouts.app')
@section('title','Meja & QR')
@section('content')
<div class="card form"><div class="sectiontitle"><h3>QRIS resto untuk bayar dari HP tamu</h3>@if($qrisMerchant)<span class="badge">{{ $qrisEnabled ? 'Aktif' : 'Terpasang, belum aktif' }}</span>@else<span class="badge warn">Belum dipasang</span>@endif</div>
<p class="muted">@if($qrisMerchant)QRIS terbaca: <b>{{ $qrisMerchant }}</b>. @endif Upload foto QRIS statis yang dipakai resto sekarang. Sistem membuat QR dengan nominal pesanan otomatis, lalu kasir mencocokkan dana masuk sebelum pesanan diproses.</p>
@permission('tables.update')<form method="post" action="{{ route('tables.qris') }}" id="qris-form">@csrf @method('PUT')
<input type="hidden" name="qris_payload" id="qris-payload">
<div class="toolbar"><label>Foto QRIS<input type="file" accept="image/*" id="qris-file"></label><input type="hidden" name="qris_enabled" value="0"><label class="inline-check"><input class="checkbox" type="checkbox" name="qris_enabled" value="1" @checked($qrisEnabled)> Tamu boleh bayar QRIS dari HP</label><button class="primary">Simpan</button></div>
<p class="muted" id="qris-status" role="status"></p></form>@endpermission</div>

@permission('tables.create')<div class="card"><form method="post" action="{{ route('tables.store') }}" class="toolbar">@csrf<label>Nomor meja <input name="number" required maxlength="30" value="{{ old('number') }}" placeholder="07"></label><label>Keterangan <input name="description" maxlength="100" value="{{ old('description') }}" placeholder="Teras, Lantai 2"></label><button class="primary">Tambah meja</button></form></div>@endpermission

<div class="toolbar"><a class="btn" href="{{ route('tables.print') }}" target="_blank">Cetak semua QR</a><span class="muted">QR berisi tautan acak ke halaman pesan meja tersebut. Pastikan alamat aplikasi bisa dibuka dari HP tamu.</span></div>
<div class="table-grid">@forelse($tables as $t)<article class="card table-card"><div class="sectiontitle"><h3>Meja {{ $t->number }}</h3><span class="badge {{ $t->is_active ? '' : 'neutral' }}">{{ $t->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>
<p class="muted">{{ $t->description ?: ' ' }}</p><div class="table-qr">{!! $qr[$t->id] !!}</div><p class="muted table-url">{{ $t->orderUrl() }}</p>
<div class="actions"><a class="btn" href="{{ route('tables.print',['table'=>$t->id]) }}" target="_blank">Cetak</a>
@permission('tables.update')<form method="post" action="{{ route('tables.token',$t) }}" onsubmit="return confirm('Ganti QR Meja {{ $t->number }}? Kartu QR lama tidak berlaku lagi.')">@csrf<button>Ganti QR</button></form>@endpermission
@permission('tables.delete')<form method="post" action="{{ route('tables.destroy',$t) }}" onsubmit="return confirm('Hapus Meja {{ $t->number }}?')">@csrf @method('DELETE')<button class="danger">Hapus</button></form>@endpermission</div>
@permission('tables.update')<details><summary>Ubah</summary><form method="post" action="{{ route('tables.update',$t) }}" class="form">@csrf @method('PUT')<label>Nomor meja<input name="number" required maxlength="30" value="{{ $t->number }}"></label><label>Keterangan<input name="description" maxlength="100" value="{{ $t->description }}"></label><input type="hidden" name="is_active" value="0"><label class="inline-check"><input class="checkbox" type="checkbox" name="is_active" value="1" @checked($t->is_active)> Aktif (menerima pesanan)</label><button class="primary">Simpan</button></form></details>@endpermission
</article>@empty<div class="card">Belum ada meja. Tambahkan meja untuk membuat QR.</div>@endforelse</div>
@endsection
@push('scripts')
<script src="{{ asset('js/vendor/jsQR.js') }}" defer></script>
<script src="{{ asset('js/tables.js').'?v='.filemtime(public_path('js/tables.js')) }}" defer></script>
@endpush
