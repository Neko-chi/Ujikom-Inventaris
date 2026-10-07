@extends('layouts.app')

@section('title', 'Pengguna')
@section('subtitle', 'Kelola akun Admin dan Operator yang dapat mengakses sistem.')

@section('aksi')
    <a href="{{ route('user.create') }}" class="btn-primary"><x-icon name="plus" />Tambah Pengguna</a>
@endsection

@section('content')
    {{-- Penjelasan singkat peran --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <div class="flex gap-4 rounded-2xl border border-brand-100 bg-brand-50/60 p-4">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white"><x-icon name="shield" /></div>
            <div>
                <p class="font-semibold text-slate-900">Admin</p>
                <p class="text-sm text-slate-600">Pengawas: memverifikasi barang keluar, mengoreksi data, mengelola kategori dan pengguna.</p>
            </div>
        </div>
        <div class="flex gap-4 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white"><x-icon name="clipboard" /></div>
            <div>
                <p class="font-semibold text-slate-900">Operator</p>
                <p class="text-sm text-slate-600">Petugas gudang: mendaftarkan barang, mencatat barang masuk, mengajukan barang keluar.</p>
            </div>
        </div>
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
                                    <div @class([
                                        'flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                                        'bg-brand-100 text-brand-700' => $u->isAdmin(),
                                        'bg-emerald-100 text-emerald-700' => $u->isOperator(),
                                    ])>{{ $u->inisial() }}</div>
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
                                <span @class([
                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset',
                                    'bg-brand-50 text-brand-700 ring-brand-600/20' => $u->isAdmin(),
                                    'bg-emerald-50 text-emerald-700 ring-emerald-600/20' => $u->isOperator(),
                                ])>
                                    <x-icon :name="$u->isAdmin() ? 'shield' : 'clipboard'" class="size-3.5" />{{ $u->role }}
                                </span>
                            </td>
                            <td class="text-sm text-slate-500">
                                @if ($u->isAdmin())
                                    <span class="font-semibold text-slate-700">{{ $u->verifikasi_barang_keluar_count }}</span> verifikasi
                                @else
                                    <span class="font-semibold text-slate-700">{{ $u->barang_masuk_count }}</span> barang masuk ·
                                    <span class="font-semibold text-slate-700">{{ $u->barang_keluar_count }}</span> pengajuan
                                @endif
                            </td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('user.edit', $u) }}" class="btn-icon" title="Ubah"><x-icon name="pencil" /></a>
                                    @unless ($u->is(auth()->user()))
                                        <form action="{{ route('user.destroy', $u) }}" method="POST" onsubmit="return confirm(@js('Hapus pengguna '.$u->nama_user.'?'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon hover:bg-rose-50 hover:text-rose-600" title="Hapus"><x-icon name="trash" /></button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
