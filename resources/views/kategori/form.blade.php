{{-- Satu form dipakai untuk tambah (create) dan ubah (edit) --}}
@extends('layouts.app')

{{-- Dapat dibuka sebagai popup (Turbo Frame) tanpa pindah halaman --}}
@section('popup', true)

@section('title', $kategori->exists ? 'Ubah Kategori' : 'Tambah Kategori')
@section('subtitle', 'Nama kategori menentukan prefix kode barang.')

@section('aksi')
    <a href="{{ route('kategori.index') }}" class="btn-secondary" data-tutup-popup><x-icon name="arrow-left" />Kembali</a>
@endsection

@section('content')
    <form action="{{ $kategori->exists ? route('kategori.update', $kategori) : route('kategori.store') }}" method="POST" class="card max-w-2xl">
        @csrf
        @if ($kategori->exists)
            @method('PUT')
        @endif

        <div class="card-header">
            <div>
                <h3 class="card-title">Informasi Kategori</h3>
                <p class="text-xs text-slate-500">Maksimal 20 karakter dan tidak boleh sama dengan kategori lain.</p>
            </div>
        </div>

        <div class="p-5">
            <label for="nama_kategori" class="label">Nama Kategori</label>
            <input id="nama_kategori" name="nama_kategori" type="text" maxlength="20" class="input"
                   value="{{ old('nama_kategori', $kategori->nama_kategori) }}" placeholder="Contoh: Alat Tulis" required autofocus>
            <x-error field="nama_kategori" />
            <p class="hint">Kategori pada kamus data memakai prefix baku (AT, FR, KN, AO, EK). Kategori baru memakai huruf awal dua kata pertama.</p>
        </div>

        <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-100 bg-slate-50/60 px-5 py-4">
            <a href="{{ route('kategori.index') }}" class="btn-secondary" data-tutup-popup>Batal</a>
            <button type="submit" class="btn-primary"><x-icon name="check" />Simpan</button>
        </div>
    </form>
@endsection
