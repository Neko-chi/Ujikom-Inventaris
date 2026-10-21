{{-- Satu form dipakai untuk tambah (create) dan ubah (edit) --}}
@extends('layouts.app')

{{-- Dapat dibuka sebagai popup (Turbo Frame) tanpa pindah halaman --}}
@section('popup', true)

@section('title', $barang->exists ? 'Ubah Barang' : 'Tambah Barang')
@section('subtitle', $barang->exists ? 'Perbarui informasi barang '.$barang->kode_barang.'.' : 'Daftarkan barang baru ke dalam gudang.')

@section('aksi')
    <a href="{{ route('barang.index') }}" class="btn-secondary" data-tutup-popup><x-icon name="arrow-left" />Kembali</a>
@endsection

@section('content')
    <form action="{{ $barang->exists ? route('barang.update', $barang) : route('barang.store') }}" method="POST" enctype="multipart/form-data" class="card max-w-3xl">
        @csrf
        @if ($barang->exists)
            @method('PUT')
        @endif

        <div class="card-header">
            <div>
                <h3 class="card-title">Informasi Barang</h3>
                <p class="text-xs text-slate-500">Kode barang dibuat otomatis dari prefix kategori.</p>
            </div>
            <span class="kode text-xs!">{{ $barang->kode_barang ?? 'Kode otomatis' }}</span>
        </div>

        <div class="grid gap-5 p-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="nama_barang" class="label">Nama Barang</label>
                <input id="nama_barang" name="nama_barang" type="text" maxlength="100" class="input" value="{{ old('nama_barang', $barang->nama_barang) }}" placeholder="Contoh: Proyektor Epson" required autofocus>
                <x-error field="nama_barang" />
            </div>

            <div>
                <label for="id_kategori" class="label">Kategori</label>
                <select id="id_kategori" name="id_kategori" class="input" required>
                    <option value="">Pilih kategori</option>
                    @foreach ($kategori as $k)
                        <option value="{{ $k->id_kategori }}" @selected(old('id_kategori', $barang->id_kategori) == $k->id_kategori)>{{ $k->nama_kategori }}</option>
                    @endforeach
                </select>
                <x-error field="id_kategori" />
            </div>

            <div>
                <label for="satuan" class="label">Satuan</label>
                <input id="satuan" name="satuan" type="text" maxlength="20" class="input" value="{{ old('satuan', $barang->satuan) }}" placeholder="buah / unit / pack" required>
                <x-error field="satuan" />
            </div>

            @if ($barang->exists)
                <div class="sm:col-span-2 rounded-xl bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-slate-200">
                    Stok saat ini <strong class="text-slate-900">{{ $barang->stok }} {{ $barang->satuan }}</strong>.
                    Stok, lokasi, dan kondisi berubah melalui transaksi barang masuk, barang keluar, dan pengembalian.
                </div>
            @else
                {{-- Stok awal (opsional): langsung dicatat di satu lokasi dengan rincian kondisinya --}}
                <div class="sm:col-span-2 space-y-4 rounded-xl border border-dashed border-slate-300 p-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Stok Awal <span class="font-normal text-slate-400">(opsional)</span></p>
                        <p class="text-xs text-slate-500">Isi bila barang sudah ada di gudang. Bisa juga dikosongkan lalu dicatat lewat Barang Masuk.</p>
                    </div>
                    <div>
                        <label for="id_lokasi" class="label">Lokasi Penyimpanan</label>
                        <select id="id_lokasi" name="id_lokasi" class="input">
                            <option value="">Pilih lokasi</option>
                            @foreach ($lokasi as $l)
                                <option value="{{ $l->id_lokasi }}" @selected(old('id_lokasi') == $l->id_lokasi)>{{ $l->nama_lokasi }} ({{ $l->jenis }})</option>
                            @endforeach
                        </select>
                        <x-error field="id_lokasi" />
                    </div>
                    <x-input-kondisi label="Jumlah per Kondisi" />
                </div>
            @endif

            <div class="sm:col-span-2">
                <x-unggah-gambar name="gambar" label="Gambar Barang" :url="$barang->gambar_url" hapus="hapus_gambar" />
            </div>
        </div>

        <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-100 bg-slate-50/60 px-5 py-4">
            <a href="{{ route('barang.index') }}" class="btn-secondary" data-tutup-popup>Batal</a>
            <button type="submit" class="btn-primary"><x-icon name="check" />Simpan</button>
        </div>
    </form>
@endsection
