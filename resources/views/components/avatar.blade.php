{{-- Foto profil pengguna; bila belum ada foto, tampil inisial nama. Pemakaian: <x-avatar :user="$user" class="size-10" /> --}}
@props(['user'])

@if ($user->foto_url)
    <img src="{{ $user->foto_url }}" alt="Foto {{ $user->nama_user }}"
         {{ $attributes->merge(['class' => 'shrink-0 rounded-full object-cover']) }}>
@else
    <span {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700']) }}>
        {{ $user->inisial() }}
    </span>
@endif
