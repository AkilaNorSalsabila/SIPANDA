@php $st = $t->status_perbaikan ?? 'normal'; @endphp
@if ($st === 'perlu_perbaikan')
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Perlu perbaikan
    </span>
@elseif ($st === 'sudah_diperbaiki')
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200 whitespace-nowrap" title="Dikoreksi petugas, menunggu verifikasi admin">
        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Menunggu verifikasi
    </span>
@elseif ($st === 'terverifikasi')
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200 whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Terverifikasi
    </span>
@else
    <span class="text-slate-300">-</span>
@endif
