{{-- Satu form dipakai untuk catat baru (Operator) dan koreksi (Admin) --}}
@extends('layouts.app')

{{-- Dapat dibuka sebagai popup (Turbo Frame) tanpa pindah halaman --}}
@section('popup', true)

@section('title', $barangMasuk->exists ? 'Koreksi Barang Masuk' : 'Catat Barang Masuk')
@section('subtitle', $barangMasuk->exists ? 'Perubahan jumlah atau barang akan menyesuaikan stok secara otomatis.' : 'Stok barang akan bertambah setelah data disimpan.')

@section('aksi')
    <a href="{{ route('barang-masuk.index') }}" class="btn-secondary" data-tutup-popup><x-icon name="arrow-left" />Kembali</a>
@endsection

@section('content')
    <form action="{{ $barangMasuk->exists ? route('barang-masuk.update', $barangMasuk) : route('barang-masuk.store') }}" method="POST" enctype="multipart/form-data" class="card max-w-3xl">
        @csrf
        @if ($barangMasuk->exists)
            @method('PUT')
        @endif

        <div class="card-header">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <x-icon name="masuk" />
                </div>
                <div>
                    <h3 class="card-title">Data Penerimaan Barang</h3>
                    <p class="text-xs text-slate-500">Isi sesuai dokumen / bukti penerimaan.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-5 p-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="id_barang" class="label">Barang</label>
                <select id="id_barang" name="id_barang" class="input" required>
                    <option value="">Pilih barang</option>
                    @foreach ($barang as $b)
                        <option value="{{ $b->id_barang }}" @selected(old('id_barang', $barangMasuk->id_barang) == $b->id_barang)>
                            {{ $b->kode_barang }} — {{ $b->nama_barang }} (stok {{ $b->stok }} {{ $b->satuan }})
                        </option>
                    @endforeach
                </select>
                <x-error field="id_barang" />
                @unless ($barangMasuk->exists)
                    <p class="hint">Barang belum terdaftar? <a href="{{ route('barang.create') }}" data-turbo-frame="modal" class="font-medium text-brand-600 hover:underline">Tambahkan barang baru</a> terlebih dahulu.</p>
                @endunless
            </div>

            <div>
                <label for="tanggal" class="label">Tanggal Diterima</label>
                <input id="tanggal" name="tanggal" type="date" class="input" value="{{ old('tanggal', $barangMasuk->tanggal?->format('Y-m-d')) }}" required>
                <x-error field="tanggal" />
            </div>

            <div>
                <label for="jumlah" class="label">Jumlah</label>
                <input id="jumlah" name="jumlah" type="number" min="1" class="input" value="{{ old('jumlah', $barangMasuk->jumlah) }}" placeholder="0" required>
                <x-error field="jumlah" />
            </div>

            <div>
                <label for="sumber_barang" class="label">Sumber Barang</label>
                <input id="sumber_barang" name="sumber_barang" type="text" maxlength="255" class="input" value="{{ old('sumber_barang', $barangMasuk->sumber_barang) }}" placeholder="Dana BOS / Pembelian / Bantuan Dinas" required>
                <x-error field="sumber_barang" />
            </div>

            <div>
                <label for="status_barang" class="label">Kondisi Barang</label>
                <select id="status_barang" name="status_barang" class="input" required>
                    @foreach (\App\Models\Barang::STATUS_BARANG as $status)
                        <option value="{{ $status }}" @selected(old('status_barang', $barangMasuk->status_barang) === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <x-error field="status_barang" />
            </div>

            <div class="sm:col-span-2">
                <x-unggah-gambar name="foto" label="Foto Bukti Penerimaan" :url="$barangMasuk->foto_url"
                                 keterangan="Misalnya foto nota, surat serah terima, atau barang yang diterima. JPG/PNG/WEBP, maks. 2 MB." />
            </div>
        </div>

        <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-100 bg-slate-50/60 px-5 py-4">
            <a href="{{ route('barang-masuk.index') }}" class="btn-secondary" data-tutup-popup>Batal</a>
            <button type="submit" class="btn-primary"><x-icon name="check" />Simpan</button>
        </div>
    </form>
@endsection
