{{-- Satu form dipakai untuk tambah (create) dan ubah (edit) --}}
@extends('layouts.app')

{{-- Dapat dibuka sebagai popup (Turbo Frame) tanpa pindah halaman --}}
@section('popup', true)

@section('title', $lokasi->exists ? 'Ubah Lokasi' : 'Tambah Lokasi')
@section('subtitle', 'Gudang = tempat penyimpanan; Ruangan = tempat barang dipakai (lab, kelas).')

@section('aksi')
    <a href="{{ route('lokasi.index') }}" class="btn-secondary" data-tutup-popup><x-icon name="arrow-left" />Kembali</a>
@endsection

@section('content')
    <form action="{{ $lokasi->exists ? route('lokasi.update', $lokasi) : route('lokasi.store') }}" method="POST" class="card max-w-2xl">
        @csrf
        @if ($lokasi->exists)
            @method('PUT')
        @endif

        <div class="card-header">
            <div>
                <h3 class="card-title">Informasi Lokasi</h3>
                <p class="text-xs text-slate-500">Nama lokasi maksimal 50 karakter dan tidak boleh sama dengan lokasi lain.</p>
            </div>
        </div>

        <div class="space-y-5 p-5">
            <div>
                <label for="nama_lokasi" class="label">Nama Lokasi</label>
                <input id="nama_lokasi" name="nama_lokasi" type="text" maxlength="50" class="input"
                       value="{{ old('nama_lokasi', $lokasi->nama_lokasi) }}" placeholder="Contoh: Gudang 2, Lab TKJ 3" required autofocus>
                <x-error field="nama_lokasi" />
            </div>

            <div>
                <span class="label">Jenis</span>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (\App\Models\Lokasi::JENIS as $jenis)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 transition hover:border-slate-300 has-checked:border-brand-600 has-checked:bg-brand-50">
                            <input type="radio" name="jenis" value="{{ $jenis }}" class="mt-1 size-4 accent-brand-600" @checked(old('jenis', $lokasi->jenis) === $jenis)>
                            <span>
                                <span class="block text-sm font-semibold text-slate-900">{{ $jenis }}</span>
                                <span class="block text-xs text-slate-500">{{ $jenis === \App\Models\Lokasi::GUDANG ? 'Tempat menyimpan barang' : 'Tempat barang dipakai, tujuan pemindahan' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-error field="jenis" />
            </div>

            <div>
                <label for="keterangan" class="label">Keterangan <span class="font-normal text-slate-400">(opsional)</span></label>
                <input id="keterangan" name="keterangan" type="text" maxlength="255" class="input"
                       value="{{ old('keterangan', $lokasi->keterangan) }}" placeholder="Contoh: Gedung B lantai 2">
                <x-error field="keterangan" />
            </div>
        </div>

        <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-100 bg-slate-50/60 px-5 py-4">
            <a href="{{ route('lokasi.index') }}" class="btn-secondary" data-tutup-popup>Batal</a>
            <button type="submit" class="btn-primary"><x-icon name="check" />Simpan</button>
        </div>
    </form>
@endsection
