@extends('layouts.app')

{{-- Dapat dibuka sebagai popup --}}
@section('popup', true)

@section('title', $barangKeluar->jenis_keluar.' #'.$barangKeluar->id_keluar)
@section('subtitle', 'Informasi pengajuan barang keluar, hasil verifikasi, dan pengembaliannya.')

@section('aksi')
    <a href="{{ route('barang-keluar.index') }}" class="btn-secondary" data-tutup-popup><x-icon name="arrow-left" />Kembali</a>
@endsection

@section('content')
    @php
        $k = $barangKeluar;
        $satuan = $k->barang->satuan;
        // Stok di lokasi asal cukup untuk tiap kondisi yang diminta?
        $stokCukup = collect($k->rincian())->every(fn ($jumlah, $kondisi) => $jumlah <= ($stokAsal[$kondisi] ?? 0));
        $hanyaRusakBerat = $k->jenis_keluar === \App\Models\BarangKeluar::PEMUSNAHAN ? ['Rusak Berat'] : null;
    @endphp

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
        {{-- Data pengajuan --}}
        <div class="card xl:col-span-2">
            <div class="card-header">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <x-icon name="keluar" />
                    </div>
                    <div>
                        <h3 class="card-title">Data Pengajuan</h3>
                        <p class="text-xs text-slate-500">Diajukan {{ $k->tanggal->translatedFormat('l, d F Y') }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <x-badge :nilai="$k->jenis_keluar" />
                    <x-badge :nilai="$k->verifikasi" />
                    @if ($k->statusPinjam())
                        <x-badge :nilai="$k->statusPinjam()" />
                    @endif
                    @if ($k->terlambat())
                        <x-badge nilai="Terlambat" />
                    @endif
                </div>
            </div>

            {{-- Barang dan rincian kondisi --}}
            <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 p-5">
                <div class="flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                    <x-icon name="cube" class="size-7" />
                </div>
                <div class="min-w-0 flex-1">
                    <span class="kode">{{ $k->barang->kode_barang }}</span>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ $k->barang->nama_barang }}</p>
                    <x-rincian-kondisi :rincian="$k->rincian()" class="mt-1" />
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-2 text-center ring-1 ring-slate-100">
                    <p class="text-xs text-slate-500">Jumlah</p>
                    <p class="text-xl font-bold text-slate-900">{{ $k->jumlah }} <span class="text-sm font-normal text-slate-500">{{ $satuan }}</span></p>
                </div>
            </div>

            <dl class="grid gap-x-6 gap-y-5 p-5 sm:grid-cols-2">
                @foreach (array_filter([
                    ['archive', 'Diambil dari', $k->lokasiAsal?->nama_lokasi ?? '-'],
                    $k->lokasiTujuan ? ['map-pin', 'Dipindahkan ke', $k->lokasiTujuan->nama_lokasi] : null,
                    ['user', $k->isPeminjaman() ? 'Peminjam' : 'Penanggung jawab', $k->pemohon],
                    ['clipboard', 'Keperluan / keterangan', $k->tujuan],
                    $k->isPeminjaman() ? ['calendar', 'Rencana kembali', $k->tanggal_kembali?->translatedFormat('d F Y') ?? '-'] : null,
                    ['users', 'Diajukan oleh (Operator)', $k->user->nama_user],
                ]) as [$ikon, $label, $nilai])
                    <div class="flex gap-3">
                        <x-icon :name="$ikon" class="mt-0.5 size-5 text-slate-400" />
                        <div>
                            <dt class="text-xs text-slate-500">{{ $label }}</dt>
                            <dd class="font-medium text-slate-900">{{ $nilai }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>

            {{-- Foto bukti yang diunggah Operator saat pengajuan --}}
            <div class="border-t border-slate-100 p-5">
                <p class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900"><x-icon name="photo" class="size-5 text-slate-400" />Foto Bukti</p>
                @if ($k->foto_url)
                    <a href="{{ $k->foto_url }}" target="_blank" rel="noopener" title="Buka ukuran penuh" class="group block w-fit">
                        <img src="{{ $k->foto_url }}" alt="Foto bukti" class="max-h-80 rounded-xl object-contain ring-1 ring-slate-200 transition group-hover:ring-brand-300">
                    </a>
                    <p class="hint">Klik gambar untuk melihat ukuran penuh.</p>
                @else
                    <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Tidak ada foto. Data ini berasal dari data sampel sebelum fitur foto bukti diwajibkan.</p>
                @endif
            </div>
        </div>

        {{-- Panel verifikasi --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Verifikasi Manager</h3>
            </div>

            <div class="p-5">
                @if (! $k->isPending())
                    @php($disetujui = $k->verifikasi === \App\Models\BarangKeluar::DISETUJUI)
                    <div @class(['mb-5 flex items-center gap-3 rounded-xl p-4', 'bg-emerald-50 text-emerald-800' => $disetujui, 'bg-rose-50 text-rose-800' => ! $disetujui])>
                        <x-icon :name="$disetujui ? 'check-circle' : 'x-circle'" class="size-8" />
                        <div>
                            <p class="font-semibold">Permintaan {{ strtolower($k->verifikasi) }}</p>
                            <p class="text-sm opacity-80">{{ $disetujui ? 'Stok telah diperbarui.' : 'Stok tidak berubah.' }}</p>
                        </div>
                    </div>
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="text-xs text-slate-500">Diverifikasi oleh</dt>
                            <dd class="mt-1 flex items-center gap-2 font-medium text-slate-900">
                                @if ($k->verifikator)
                                    <span class="flex size-7 items-center justify-center rounded-full bg-brand-600 text-[10px] font-bold text-on-brand">{{ $k->verifikator->inisial() }}</span>
                                @endif
                                {{ $k->verifikator?->nama_user ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Tanggal verifikasi</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $k->tanggal_verifikasi?->translatedFormat('d F Y') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Catatan</dt>
                            <dd class="mt-1 rounded-xl bg-slate-50 p-3 text-slate-700">{{ $k->catatan_verifikasi ?: 'Tidak ada catatan.' }}</dd>
                        </div>
                    </dl>
                @elseif (auth()->user()->isManager())
                    <div class="mb-4 rounded-xl bg-slate-50 p-3 text-xs text-slate-600 ring-1 ring-slate-200">
                        <p class="font-semibold text-slate-800">Stok di {{ $k->lokasiAsal?->nama_lokasi }} saat ini</p>
                        <x-rincian-kondisi :rincian="$stokAsal" class="mt-1.5" />
                    </div>
                    @unless ($stokCukup)
                        <div class="mb-4 flex gap-2 rounded-xl bg-rose-50 p-3 text-sm text-rose-700">
                            <x-icon name="warning" class="size-5" />
                            <p>Stok di lokasi asal tidak mencukupi rincian ini. Koreksi jumlahnya atau tolak permintaan.</p>
                        </div>
                    @endunless

                    <form method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        {{-- Manager memeriksa rincian kondisi dari Operator dan boleh mengoreksinya --}}
                        <x-input-kondisi :nilai="$k->rincian()" :hanya="$hanyaRusakBerat" label="Periksa Rincian Kondisi" :satuan="$satuan"
                                         petunjuk="Ubah bila kondisi fisik berbeda; koreksi dicatat otomatis." />
                        <div>
                            <label for="catatan_verifikasi" class="label">Catatan <span class="font-normal text-slate-400">(wajib jika ditolak)</span></label>
                            <textarea id="catatan_verifikasi" name="catatan_verifikasi" rows="3" maxlength="255" class="input resize-none" placeholder="Contoh: Silakan ambil di gudang utama.">{{ old('catatan_verifikasi') }}</textarea>
                            <x-error field="catatan_verifikasi" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="submit" formaction="{{ route('barang-keluar.tolak', $k) }}" class="btn-secondary text-rose-600! hover:bg-rose-50!"
                                    onclick="return confirm('Tolak permintaan ini?')"><x-icon name="x" />Tolak</button>
                            <button type="submit" formaction="{{ route('barang-keluar.setujui', $k) }}" class="btn-success"
                                    onclick="return confirm('Setujui permintaan ini? Stok akan diperbarui.')"><x-icon name="check" />Setujui</button>
                        </div>
                    </form>
                @else
                    <div class="flex flex-col items-center py-6 text-center">
                        <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-500">
                            <x-icon name="clock" class="size-6" />
                        </div>
                        <p class="font-semibold text-slate-800">Menunggu verifikasi</p>
                        <p class="mt-1 text-sm text-slate-500">Stok belum berubah sampai Manager menyetujui permintaan ini.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Pengembalian (khusus peminjaman yang sudah disetujui) --}}
    @if ($k->isPeminjaman() && $k->verifikasi === \App\Models\BarangKeluar::DISETUJUI)
        @php($sisa = $k->sisaPinjaman())
        <div class="card mt-6">
            <div class="card-header">
                <h3 class="card-title flex items-center gap-2"><x-icon name="refresh" class="size-5 text-slate-500" />Pengembalian</h3>
                <p class="text-sm text-slate-500">
                    Dipinjam <strong class="text-slate-900">{{ $k->jumlah }}</strong> ·
                    Kembali <strong class="text-slate-900">{{ $k->jumlahKembali() }}</strong> ·
                    Sisa <strong @class(['text-rose-600' => $sisa > 0, 'text-emerald-600' => $sisa === 0])>{{ $sisa }}</strong> {{ $satuan }}
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="table-data">
                    <thead><tr><th>Tanggal</th><th class="text-right">Jumlah</th><th>Kondisi saat kembali</th><th>Disimpan di</th><th>Catatan</th><th>Dicatat oleh</th></tr></thead>
                    <tbody>
                        @forelse ($k->pengembalian as $p)
                            <tr>
                                <td class="whitespace-nowrap">{{ $p->tanggal->translatedFormat('d M Y') }}</td>
                                <td class="text-right font-semibold text-emerald-600">+{{ $p->jumlah }}</td>
                                <td><x-rincian-kondisi :rincian="$p->rincian()" /></td>
                                <td class="whitespace-nowrap">{{ $p->lokasi->nama_lokasi }}</td>
                                <td>{{ $p->catatan ?: '-' }}</td>
                                <td class="whitespace-nowrap">{{ $p->user->nama_user }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty icon="refresh" judul="Belum ada pengembalian" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sisa > 0 && auth()->user()->isOperator())
                <form action="{{ route('pengembalian.store', $k) }}" method="POST" enctype="multipart/form-data" class="grid gap-5 border-t border-slate-100 p-5 sm:grid-cols-2">
                    @csrf
                    <p class="sm:col-span-2 text-sm font-semibold text-slate-900">Catat Pengembalian</p>
                    <div>
                        <label for="tanggal_kembali_aktual" class="label">Tanggal Kembali</label>
                        <input id="tanggal_kembali_aktual" name="tanggal" type="date" class="input" value="{{ old('tanggal', now()->format('Y-m-d')) }}" required>
                        <x-error field="tanggal" />
                    </div>
                    <div>
                        <label for="id_lokasi_kembali" class="label">Disimpan di Lokasi</label>
                        <select id="id_lokasi_kembali" name="id_lokasi" class="input" required>
                            @foreach ($lokasi as $l)
                                <option value="{{ $l->id_lokasi }}" @selected(old('id_lokasi', $k->id_lokasi_asal) == $l->id_lokasi)>{{ $l->nama_lokasi }} ({{ $l->jenis }})</option>
                            @endforeach
                        </select>
                        <x-error field="id_lokasi" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-kondisi :nilai="[]" label="Jumlah Dikembalikan per Kondisi" :satuan="$satuan"
                                         petunjuk="Catat kondisi saat kembali; barang bisa saja sudah rusak ringan atau berat. Maksimal sisa {{ $sisa }} {{ $satuan }}." />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="catatan" class="label">Catatan <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input id="catatan" name="catatan" type="text" maxlength="255" class="input" value="{{ old('catatan') }}" placeholder="Contoh: 2 unit retak di bagian casing">
                        <x-error field="catatan" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-unggah-gambar name="foto" label="Foto Bukti Pengembalian" keterangan="Opsional. JPG/PNG/WEBP, maks. 2 MB." />
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button type="submit" class="btn-primary"><x-icon name="check" />Simpan Pengembalian</button>
                    </div>
                </form>
            @endif
        </div>
    @endif
@endsection
