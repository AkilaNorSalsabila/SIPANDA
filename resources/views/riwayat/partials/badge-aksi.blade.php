{{-- Badge aksi. Kelas ditulis lengkap di sini (bukan di Model) supaya ikut terbaca Tailwind saat build. --}}
@php
    $warna = [
        'tambah'      => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'ubah'        => 'bg-sky-50 text-sky-700 ring-sky-200',
        'hapus'       => 'bg-red-50 text-red-700 ring-red-200',
        'import'      => 'bg-violet-50 text-violet-700 ring-violet-200',
        'setujui'     => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'tolak'       => 'bg-red-50 text-red-700 ring-red-200',
        'nonaktifkan' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'daftar'      => 'bg-teal-50 text-teal-700 ring-teal-200',
        'login'       => 'bg-slate-100 text-slate-600 ring-slate-200',
        'logout'      => 'bg-slate-100 text-slate-600 ring-slate-200',
    ][$aksi] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
@endphp
<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap {{ $warna }}">
    {{ \App\Models\Riwayat::AKSI[$aksi] ?? ucfirst($aksi) }}
</span>
