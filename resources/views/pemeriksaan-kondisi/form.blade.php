@extends('layouts.app')

{{-- Dapat dibuka sebagai popup (Turbo Frame) tanpa pindah halaman --}}
@section('popup', true)

@section('title', 'Ajukan Pemeriksaan Kondisi')
@section('subtitle', 'Kondisi fisik barang berbeda dengan data? Catat di sini. Diverifikasi Manager sebelum stok berubah.')

@section('aksi')
    <a href="{{ route('pemeriksaan-kondisi.index') }}" class="btn-secondary" data-tutup-popup><x-icon name="arrow-left" />Kembali</a>
@endsection

@section('content')
    <form action="{{ route('pemeriksaan-kondisi.store') }}" method="POST" enctype="multipart/form-data" class="card max-w-3xl" data-form-pemeriksaan>
        @csrf
        {{-- Stok per barang, lokasi, dan kondisi untuk info "tercatat" (dibaca resources/js/form-kondisi.js) --}}
        <script type="application/json" data-peta-stok>@json($petaStok)</script>

        <div class="card-header">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <x-icon name="clipboard" />
                </div>
                <div>
                    <h3 class="card-title">Hasil Pemeriksaan</h3>
                    <p class="text-xs text-slate-500">Total stok tetap; hanya jumlah per kondisi yang disesuaikan.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-5 p-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="id_barang" class="label">Barang</label>
                <select id="id_barang" name="id_barang" class="input" required data-pilih-barang>
                    <option value="">Pilih barang</option>
                    @foreach ($barang as $b)
                        <option value="{{ $b->id_barang }}" data-satuan="{{ $b->satuan }}" @selected(old('id_barang') == $b->id_barang)>
                            {{ $b->kode_barang }} — {{ $b->nama_barang }} (stok {{ $b->stok }} {{ $b->satuan }})
                        </option>
                    @endforeach
                </select>
                <x-error field="id_barang" />
            </div>

            <div>
                <label for="id_lokasi" class="label">Lokasi Barang</label>
                <select id="id_lokasi" name="id_lokasi" class="input" required data-pilih-lokasi-asal>
                    <option value="">Pilih lokasi</option>
                    @foreach ($lokasi as $l)
                        <option value="{{ $l->id_lokasi }}" @selected(old('id_lokasi') == $l->id_lokasi)>{{ $l->nama_lokasi }} ({{ $l->jenis }})</option>
                    @endforeach
                </select>
                <x-error field="id_lokasi" />
            </div>

            <div>
                <label for="tanggal" class="label">Tanggal Pemeriksaan</label>
                <input id="tanggal" name="tanggal" type="date" class="input" value="{{ old('tanggal', now()->format('Y-m-d')) }}" required>
                <x-error field="tanggal" />
            </div>

            {{-- Stok tercatat di lokasi ini (diisi JavaScript) --}}
            <div class="sm:col-span-2 hidden rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200" data-info-stok></div>

            <div>
                <label for="kondisi_asal" class="label">Kondisi di Data</label>
                <select id="kondisi_asal" name="kondisi_asal" class="input" required data-kondisi-asal>
                    @foreach (\App\Models\Barang::KONDISI as $kondisi)
                        <option value="{{ $kondisi }}" @selected(old('kondisi_asal', \App\Models\Barang::BAIK) === $kondisi)>{{ $kondisi }}</option>
                    @endforeach
                </select>
                <x-error field="kondisi_asal" />
            </div>

            <div>
                <label for="kondisi_baru" class="label">Kondisi Sebenarnya</label>
                <select id="kondisi_baru" name="kondisi_baru" class="input" required>
                    @foreach (\App\Models\Barang::KONDISI as $kondisi)
                        <option value="{{ $kondisi }}" @selected(old('kondisi_baru', \App\Models\Barang::RUSAK_RINGAN) === $kondisi)>{{ $kondisi }}</option>
                    @endforeach
                </select>
                <x-error field="kondisi_baru" />
            </div>

            <div>
                <label for="jumlah" class="label">Jumlah</label>
                <input id="jumlah" name="jumlah" type="number" min="1" inputmode="numeric" class="input" value="{{ old('jumlah') }}" placeholder="Contoh: 10" required data-jumlah-pemeriksaan>
                <p class="hint">Jumlah barang yang kondisinya berbeda, maksimal stok kondisi di data.</p>
                <x-error field="jumlah" />
            </div>

            <div class="sm:col-span-2">
                <label for="alasan" class="label">Alasan / Temuan</label>
                <input id="alasan" name="alasan" type="text" maxlength="255" class="input" value="{{ old('alasan') }}" placeholder="Contoh: Saat dicek, 10 meja kakinya goyang dan permukaan tergores" required>
                <x-error field="alasan" />
            </div>

            <div class="sm:col-span-2">
                <x-unggah-gambar name="foto" label="Foto Bukti" wajib
                                 keterangan="Wajib. Foto kondisi fisik barang saat diperiksa. JPG/PNG/WEBP, maks. 2 MB." />
            </div>
        </div>

        <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-100 bg-slate-50/60 px-5 py-4">
            <a href="{{ route('pemeriksaan-kondisi.index') }}" class="btn-secondary" data-tutup-popup>Batal</a>
            <button type="submit" class="btn-primary"><x-icon name="clipboard" />Ajukan Pemeriksaan</button>
        </div>
    </form>
@endsection
