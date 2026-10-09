@extends('layouts.app')

{{-- Dibuka sebagai panel kanan (Turbo Frame "laci") lewat foto profil di topbar / sidebar --}}
@section('popup', true)

@section('title', 'Profil Saya')
@section('subtitle', 'Ubah nama, foto profil, dan password akun Anda.')

@section('content')
    <div class="mx-auto max-w-xl space-y-6">
        {{-- Ringkasan akun --}}
        <div class="card overflow-hidden">
            <div class="h-20 bg-gradient-to-br from-slate-200 to-slate-300"></div>
            <div class="-mt-10 flex flex-col items-center px-6 pb-5 text-center">
                <x-avatar :user="$user" class="size-20 text-xl ring-4 ring-surface" />
                <h3 class="mt-3 text-lg font-bold text-slate-900">{{ $user->nama_user }}</h3>
                <p class="text-sm text-slate-500">{{ '@'.$user->username }}</p>
                <span class="mt-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $user->role }}</span>
                <p class="mt-3 text-xs text-slate-400">Username dan role hanya dapat diubah oleh Admin.</p>
            </div>
        </div>

        <form action="{{ route('profil.update') }}" method="POST" enctype="multipart/form-data" class="card">
            @csrf
            @method('PUT')

            <div class="card-header">
                <div>
                    <h3 class="card-title">Data Diri</h3>
                    <p class="text-xs text-slate-500">Foto profil tampil di menu samping dan daftar pengguna.</p>
                </div>
            </div>
            <div class="space-y-5 p-5">
                <div>
                    <label for="nama_user" class="label">Nama Lengkap</label>
                    <input id="nama_user" name="nama_user" type="text" maxlength="100" class="input" value="{{ old('nama_user', $user->nama_user) }}" required>
                    <x-error field="nama_user" />
                </div>

                <x-unggah-gambar name="foto" label="Foto Profil" :url="$user->foto_url" hapus="hapus_foto" />
            </div>

            <div class="card-header border-t">
                <div>
                    <h3 class="card-title">Ganti Password</h3>
                    <p class="text-xs text-slate-500">Kosongkan bagian ini bila tidak ingin mengganti password.</p>
                </div>
            </div>
            <div class="space-y-5 p-5">
                <div>
                    <label for="password_lama" class="label">Password Lama</label>
                    <input id="password_lama" name="password_lama" type="password" class="input" autocomplete="current-password">
                    <x-error field="password_lama" />
                </div>
                <div>
                    <label for="password" class="label">Password Baru</label>
                    <input id="password" name="password" type="password" class="input" autocomplete="new-password">
                    <x-error field="password" />
                </div>
                <div>
                    <label for="password_confirmation" class="label">Ulangi Password Baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password">
                </div>
            </div>

            <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                <button type="submit" class="btn-primary"><x-icon name="check" />Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection
