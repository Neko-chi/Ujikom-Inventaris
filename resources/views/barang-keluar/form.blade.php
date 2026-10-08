@extends('layouts.app')

@section('title', 'Ajukan Barang Keluar')
@section('subtitle', 'Permintaan akan diverifikasi oleh Manager sebelum stok dikurangi.')

@section('aksi')
    <a href="{{ route('barang-keluar.index') }}" class="btn-secondary"><x-icon name="arrow-left" />Kembali</a>
@endsection

@section('content')
    <div class="mb-6 flex max-w-3xl gap-3 rounded-xl border border-brand-100 bg-brand-50 p-4 text-sm text-brand-900">
        <x-icon name="info" class="size-5 text-brand-500" />
        <p>Permintaan akan berstatus <strong>Pending</strong> dan stok belum berkurang sampai Manager memberikan verifikasi <strong>Disetujui</strong>. Pengajuan tidak dapat diubah setelah dikirim, jadi periksa kembali datanya.</p>
    </div>

    <form action="{{ route('barang-keluar.store') }}" method="POST" enctype="multipart/form-data" class="card max-w-3xl">
        @csrf

        <div class="card-header">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <x-icon name="keluar" />
                </div>
                <div>
                    <h3 class="card-title">Data Permintaan</h3>
                    <p class="text-xs text-slate-500">Hanya barang dengan stok tersedia yang dapat dipilih.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-5 p-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="id_barang" class="label">Barang</label>
                <select id="id_barang" name="id_barang" class="input" required>
                    <option value="">Pilih barang</option>
                    @foreach ($barang as $b)
                        <option value="{{ $b->id_barang }}" @selected(old('id_barang', $barangKeluar->id_barang) == $b->id_barang)>
                            {{ $b->kode_barang }} — {{ $b->nama_barang }} (stok {{ $b->stok }} {{ $b->satuan }})
                        </option>
                    @endforeach
                </select>
                <x-error field="id_barang" />
            </div>

            <div>
                <label for="tanggal" class="label">Tanggal</label>
                <input id="tanggal" name="tanggal" type="date" class="input" value="{{ old('tanggal', $barangKeluar->tanggal?->format('Y-m-d')) }}" required>
                <x-error field="tanggal" />
            </div>

            <div>
                <label for="jumlah" class="label">Jumlah</label>
                <input id="jumlah" name="jumlah" type="number" min="1" class="input" value="{{ old('jumlah', $barangKeluar->jumlah) }}" placeholder="0" required>
                <x-error field="jumlah" />
            </div>

            <div>
                <label for="pemohon" class="label">Pemohon</label>
                <input id="pemohon" name="pemohon" type="text" maxlength="100" class="input" value="{{ old('pemohon', $barangKeluar->pemohon) }}" placeholder="Nama guru / staf yang meminta" required>
                <x-error field="pemohon" />
            </div>

            <div>
                <label for="tujuan" class="label">Tujuan</label>
                <input id="tujuan" name="tujuan" type="text" maxlength="100" class="input" value="{{ old('tujuan', $barangKeluar->tujuan) }}" placeholder="Contoh: Lab TKJ 3" required>
                <x-error field="tujuan" />
            </div>

            <div>
                <label for="status_barang" class="label">Kondisi Barang</label>
                <select id="status_barang" name="status_barang" class="input" required>
                    @foreach (\App\Models\Barang::STATUS_BARANG as $status)
                        <option value="{{ $status }}" @selected(old('status_barang', $barangKeluar->status_barang) === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <x-error field="status_barang" />
            </div>

            <div class="sm:col-span-2">
                <x-unggah-gambar name="foto" label="Foto Bukti Permintaan" wajib
                                 keterangan="Wajib. Misalnya foto barang yang akan dikeluarkan atau surat permintaan. JPG/PNG/WEBP, maks. 2 MB." />
            </div>
        </div>

        <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-100 bg-slate-50/60 px-5 py-4">
            <a href="{{ route('barang-keluar.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary"><x-icon name="keluar" />Ajukan Permintaan</button>
        </div>
    </form>
@endsection
