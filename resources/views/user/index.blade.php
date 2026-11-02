@extends('layouts.app')

@section('title', 'Pengguna')
@section('subtitle', 'Tambah dan hapus akun yang dapat mengakses sistem. Setiap pengguna mengubah data dan password-nya sendiri lewat Profil.')

@section('aksi')
    <a href="{{ route('user.create') }}" data-turbo-frame="modal" class="btn-primary"><x-icon name="plus" />Tambah Pengguna</a>
@endsection

@section('content')
    {{-- Penjelasan singkat peran --}}
    <div class="mb-6 grid gap-4 md:grid-cols-3">
        @foreach (\App\Models\User::INFO_ROLE as $role => $info)
            {{-- Kotak memakai kelas .card sehingga warnanya mengikuti tema terang / gelap --}}
            <div class="card flex gap-4 p-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-on-brand"><x-icon :name="$info['ikon']" /></div>
                <div>
                    <p class="font-semibold text-slate-900">{{ $role }}</p>
                    <p class="text-sm text-slate-600">{{ $info['keterangan'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-data">
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Role</th>
                        <th>Aktivitas</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-avatar :user="$u" class="size-10 text-sm" />
                                    <div>
                                        <p class="font-semibold text-slate-900">
                                            {{ $u->nama_user }}
                                            @if ($u->is(auth()->user()))
                                                <span class="ml-1 rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-500">Anda</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-slate-500">{{ '@'.$u->username }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php($info = \App\Models\User::INFO_ROLE[$u->role])
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $info['badge'] }}">
                                    <x-icon :name="$info['ikon']" class="size-3.5" />{{ $u->role }}
                                </span>
                            </td>
                            <td class="text-sm text-slate-500">
                                @if ($u->isManager())
                                    <span class="font-semibold text-slate-700">{{ $u->verifikasi_barang_keluar_count }}</span> verifikasi
                                @elseif ($u->isOperator())
                                    <span class="font-semibold text-slate-700">{{ $u->barang_masuk_count }}</span> barang masuk ·
                                    <span class="font-semibold text-slate-700">{{ $u->barang_keluar_count }}</span> pengajuan
                                @else
                                    Pengelola sistem
                                @endif
                            </td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    @if ($u->is(auth()->user()))
                                        {{-- Akun sendiri diubah lewat panel Profil --}}
                                        <a href="{{ route('profil.edit') }}" data-turbo-frame="laci" class="btn-icon" title="Ubah lewat Profil"><x-icon name="pencil" /></a>
                                    @else
                                        <form action="{{ route('user.destroy', $u) }}" method="POST" onsubmit="return confirm(@js('Hapus pengguna '.$u->nama_user.'?'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon hover:bg-rose-50 hover:text-rose-600" title="Hapus"><x-icon name="trash" /></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
