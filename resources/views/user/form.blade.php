{{-- Satu form dipakai untuk tambah (create) dan ubah (edit) --}}
@extends('layouts.app')

{{-- Dapat dibuka sebagai popup (Turbo Frame) tanpa pindah halaman --}}
@section('popup', true)

@section('title', $user->exists ? 'Ubah Pengguna' : 'Tambah Pengguna')
@section('subtitle', $user->exists ? 'Perbarui data akun '.$user->nama_user.'.' : 'Buat akun baru untuk Admin atau Operator.')

@section('aksi')
    <a href="{{ route('user.index') }}" class="btn-secondary" data-tutup-popup><x-icon name="arrow-left" />Kembali</a>
@endsection

@section('content')
    <form action="{{ $user->exists ? route('user.update', $user) : route('user.store') }}" method="POST" class="card max-w-3xl">
        @csrf
        @if ($user->exists)
            @method('PUT')
        @endif

        {{-- Data akun --}}
        <div class="card-header">
            <div>
                <h3 class="card-title">Data Akun</h3>
                <p class="text-xs text-slate-500">Username dipakai untuk login dan tidak boleh sama.</p>
            </div>
        </div>
        <div class="grid gap-5 p-5 sm:grid-cols-2">
            <div>
                <label for="nama_user" class="label">Nama Lengkap</label>
                <input id="nama_user" name="nama_user" type="text" maxlength="100" class="input" value="{{ old('nama_user', $user->nama_user) }}" placeholder="Contoh: Siti Aminah" required autofocus>
                <x-error field="nama_user" />
            </div>

            <div>
                <label for="username" class="label">Username</label>
                <input id="username" name="username" type="text" maxlength="50" class="input" value="{{ old('username', $user->username) }}" placeholder="Contoh: operator_siti" required>
                <p class="hint">Huruf, angka, tanda hubung, dan garis bawah.</p>
                <x-error field="username" />
            </div>

            <div class="sm:col-span-2">
                <span class="label">Role</span>
                @if ($user->is(auth()->user()))
                    {{-- Role akun sendiri dikunci agar Admin tidak kehilangan hak aksesnya karena salah pilih --}}
                    <input type="hidden" name="role" value="{{ $user->role }}">
                    <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        <x-icon name="lock" class="size-5 text-slate-400" />
                        <span><strong class="text-slate-800">{{ $user->role }}</strong> — Role akun Anda sendiri tidak dapat diubah.</span>
                    </div>
                @else
                    {{-- Pilihan role dalam bentuk kartu --}}
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach (\App\Models\User::INFO_ROLE as $role => ['ikon' => $ikon, 'keterangan' => $keterangan])
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-slate-300 has-checked:border-brand-500 has-checked:bg-brand-50/50 has-checked:ring-4 has-checked:ring-brand-500/10">
                                <input type="radio" name="role" value="{{ $role }}" class="mt-1 size-4 accent-brand-600" @checked(old('role', $user->role) === $role) required>
                                <div>
                                    <p class="flex items-center gap-1.5 font-semibold text-slate-900"><x-icon :name="$ikon" class="size-4 text-slate-500" />{{ $role }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $keterangan }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
                <x-error field="role" />
            </div>
        </div>

        {{-- Password --}}
        <div class="card-header border-t">
            <div>
                <h3 class="card-title">Password</h3>
                <p class="text-xs text-slate-500">{{ $user->exists ? 'Kosongkan jika tidak ingin mengganti password.' : 'Minimal 6 karakter, disimpan dalam bentuk terenkripsi (hash).' }}</p>
            </div>
        </div>
        <div class="grid gap-5 p-5 sm:grid-cols-2">
            <div>
                <label for="password" class="label">Password {{ $user->exists ? 'Baru' : '' }}</label>
                <input id="password" name="password" type="password" class="input" autocomplete="new-password" {{ $user->exists ? '' : 'required' }}>
                <x-error field="password" />
            </div>
            <div>
                <label for="password_confirmation" class="label">Ulangi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password">
            </div>
        </div>

        <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-100 bg-slate-50/60 px-5 py-4">
            <a href="{{ route('user.index') }}" class="btn-secondary" data-tutup-popup>Batal</a>
            <button type="submit" class="btn-primary"><x-icon name="check" />Simpan</button>
        </div>
    </form>
@endsection
