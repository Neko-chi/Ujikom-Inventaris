@extends('layouts.app')

@section('title', 'Profil Saya')
@section('subtitle', 'Ubah nama, foto profil, dan password akun Anda.')

@section('content')
    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
        {{-- Kartu ringkasan akun --}}
        <div class="card overflow-hidden">
            <div class="h-24 bg-gradient-to-br from-brand-600 to-brand-900"></div>
            <div class="-mt-12 flex flex-col items-center px-6 pb-6 text-center">
                <x-avatar :user="$user" class="size-24 text-2xl ring-4 ring-white" />
                <h2 class="mt-3 text-lg font-bold text-slate-900">{{ $user->nama_user }}</h2>
                <p class="text-sm text-slate-500">{{ '@'.$user->username }}</p>
                <span class="mt-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $user->role }}</span>
                <p class="mt-4 text-xs text-slate-400">Username dan role hanya dapat diubah oleh Admin.</p>
            </div>
        </div>

        <form action="{{ route('profil.update') }}" method="POST" enctype="multipart/form-data" class="card xl:col-span-2">
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
            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="password_lama" class="label">Password Lama</label>
                    <input id="password_lama" name="password_lama" type="password" class="input sm:max-w-sm" autocomplete="current-password">
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
