@extends('layouts.app')

{{-- Dapat dibuka sebagai popup (Turbo Frame) tanpa pindah halaman --}}
@section('popup', true)

@section('title', 'Ajukan Barang Keluar')
@section('subtitle', 'Peminjaman, pemindahan ke ruangan lain, atau pemusnahan. Diverifikasi Manager sebelum stok berubah.')

@section('aksi')
    <a href="{{ route('barang-keluar.index') }}" class="btn-secondary" data-tutup-popup><x-icon name="arrow-left" />Kembali</a>
@endsection

@section('content')
    @php($jenisDipilih = old('jenis_keluar', $barangKeluar->jenis_keluar))

    <form action="{{ route('barang-keluar.store') }}" method="POST" enctype="multipart/form-data" class="card max-w-3xl" data-form-keluar>
        @csrf
        {{-- Stok per barang, lokasi, dan kondisi untuk info "tersedia" (dibaca resources/js/app.js) --}}
        <script type="application/json" data-peta-stok>@json($petaStok)</script>

        <div class="card-header">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <x-icon name="keluar" />
                </div>
                <div>
                    <h3 class="card-title">Data Permintaan</h3>
                    <p class="text-xs text-slate-500">Status Pending; stok belum berubah sampai Manager menyetujui.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-5 p-5 sm:grid-cols-2">
            {{-- Jenis barang keluar --}}
            <div class="sm:col-span-2">
                <span class="label">Jenis Barang Keluar</span>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach (\App\Models\BarangKeluar::INFO_JENIS as $jenis => $info)
                        <label class="flex cursor-pointer flex-col gap-1 rounded-xl border border-slate-200 p-3 transition hover:border-slate-300 has-checked:border-brand-600 has-checked:bg-brand-50">
                            <span class="flex items-center gap-2">
                                <input type="radio" name="jenis_keluar" value="{{ $jenis }}" class="size-4 accent-brand-600" data-pilih-jenis @checked($jenisDipilih === $jenis)>
                                <span class="text-sm font-semibold text-slate-900">{{ $jenis }}</span>
                            </span>
                            <span class="text-xs text-slate-500">{{ $info }}</span>
                        </label>
                    @endforeach
                </div>
                <x-error field="jenis_keluar" />
            </div>

            <div class="sm:col-span-2">
                <label for="id_barang" class="label">Barang</label>
                <select id="id_barang" name="id_barang" class="input" required data-pilih-barang>
                    <option value="">Pilih barang</option>
                    @foreach ($barang as $b)
                        <option value="{{ $b->id_barang }}" data-satuan="{{ $b->satuan }}" @selected(old('id_barang', $barangKeluar->id_barang) == $b->id_barang)>
                            {{ $b->kode_barang }} — {{ $b->nama_barang }} (stok {{ $b->stok }} {{ $b->satuan }})
                        </option>
                    @endforeach
                </select>
                <x-error field="id_barang" />
            </div>

            <div>
                <label for="id_lokasi_asal" class="label">Diambil dari Lokasi</label>
                <select id="id_lokasi_asal" name="id_lokasi_asal" class="input" required data-pilih-lokasi-asal>
                    <option value="">Pilih lokasi</option>
                    @foreach ($lokasi as $l)
                        <option value="{{ $l->id_lokasi }}" @selected(old('id_lokasi_asal', $barangKeluar->id_lokasi_asal) == $l->id_lokasi)>{{ $l->nama_lokasi }} ({{ $l->jenis }})</option>
                    @endforeach
                </select>
                <x-error field="id_lokasi_asal" />
            </div>

            <div>
                <label for="tanggal" class="label">Tanggal</label>
                <input id="tanggal" name="tanggal" type="date" class="input" value="{{ old('tanggal', $barangKeluar->tanggal?->format('Y-m-d')) }}" required>
                <x-error field="tanggal" />
            </div>

            {{-- Stok tersedia di lokasi asal (diisi JavaScript) --}}
            <div class="sm:col-span-2 hidden rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200" data-info-stok></div>

            <div class="sm:col-span-2">
                <x-input-kondisi :nilai="[]" label="Jumlah per Kondisi"
                                 :hanya="$jenisDipilih === \App\Models\BarangKeluar::PEMUSNAHAN ? ['Rusak Berat'] : null"
                                 petunjuk="Cek fisik barang: pisahkan jumlah yang Baik, Rusak Ringan, dan Rusak Berat." />
            </div>

            {{-- Khusus Pemindahan --}}
            <div class="sm:col-span-2" data-hanya-jenis="Pemindahan">
                <label for="id_lokasi_tujuan" class="label">Dipindahkan ke Ruangan</label>
                <select id="id_lokasi_tujuan" name="id_lokasi_tujuan" class="input">
                    <option value="">Pilih lokasi tujuan</option>
                    @foreach ($lokasi as $l)
                        <option value="{{ $l->id_lokasi }}" @selected(old('id_lokasi_tujuan') == $l->id_lokasi)>{{ $l->nama_lokasi }} ({{ $l->jenis }})</option>
                    @endforeach
                </select>
                <x-error field="id_lokasi_tujuan" />
                <p class="hint">Barang tetap tercatat sebagai stok, hanya lokasinya yang berpindah.</p>
            </div>

            {{-- Khusus Peminjaman --}}
            <div data-hanya-jenis="Peminjaman">
                <label for="tanggal_kembali" class="label">Rencana Tanggal Kembali</label>
                <input id="tanggal_kembali" name="tanggal_kembali" type="date" class="input" value="{{ old('tanggal_kembali') }}">
                <x-error field="tanggal_kembali" />
            </div>

            <div>
                <label for="pemohon" class="label" data-label-pemohon>Peminjam / Penanggung Jawab</label>
                <input id="pemohon" name="pemohon" type="text" maxlength="100" class="input" value="{{ old('pemohon', $barangKeluar->pemohon) }}" placeholder="Nama guru / staf" required>
                <x-error field="pemohon" />
            </div>

            <div class="sm:col-span-2">
                <label for="tujuan" class="label">Keperluan / Keterangan</label>
                <input id="tujuan" name="tujuan" type="text" maxlength="100" class="input" value="{{ old('tujuan', $barangKeluar->tujuan) }}" placeholder="Contoh: Praktik jaringan kelas XI, persiapan lomba" required>
                <x-error field="tujuan" />
            </div>

            <div class="sm:col-span-2">
                <x-unggah-gambar name="foto" label="Foto Bukti" wajib
                                 keterangan="Wajib. Foto barang yang akan dikeluarkan, surat permintaan, atau berita acara pemusnahan. JPG/PNG/WEBP, maks. 2 MB." />
            </div>
        </div>

        <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-100 bg-slate-50/60 px-5 py-4">
            <a href="{{ route('barang-keluar.index') }}" class="btn-secondary" data-tutup-popup>Batal</a>
            <button type="submit" class="btn-primary"><x-icon name="keluar" />Ajukan Permintaan</button>
        </div>
    </form>
@endsection
