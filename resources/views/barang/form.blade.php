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
                <label for="status_barang" class="label">Kondisi Barang</label>
                <select id="status_barang" name="status_barang" class="input" required>
                    @foreach (\App\Models\Barang::STATUS_BARANG as $status)
                        <option value="{{ $status }}" @selected(old('status_barang', $barang->status_barang) === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <x-error field="status_barang" />
            </div>

            <div>
                <label for="stok" class="label">{{ $barang->exists ? 'Stok Saat Ini' : 'Stok Awal' }}</label>
                @if ($barang->exists)
                    <input id="stok" type="number" class="input" value="{{ $barang->stok }}" disabled>
                    <p class="hint">Stok berubah melalui transaksi barang masuk / keluar.</p>
                @else
                    <input id="stok" name="stok" type="number" min="0" class="input" value="{{ old('stok', $barang->stok) }}" required>
                    <x-error field="stok" />
                @endif
            </div>

            <div>
                <label for="satuan" class="label">Satuan</label>
                <input id="satuan" name="satuan" type="text" maxlength="20" class="input" value="{{ old('satuan', $barang->satuan) }}" placeholder="buah / unit / pack" required>
                <x-error field="satuan" />
            </div>

            <div class="sm:col-span-2">
                <label for="lokasi" class="label">Lokasi Penyimpanan</label>
                <input id="lokasi" name="lokasi" type="text" maxlength="100" class="input" value="{{ old('lokasi', $barang->lokasi) }}" placeholder="Contoh: gudang utama" required>
                <x-error field="lokasi" />
            </div>

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
