<x-app-layout>
<div class="p-4 sm:p-6 lg:p-8 max-w-5xl">

    <a href="{{ route('admin.penugasan.index', array_filter(['kegiatan' => $kegiatan?->id])) }}" class="text-xs font-semibold text-teal-700 hover:underline">&larr; Kembali ke Penugasan Mitra</a>

    <div class="mt-2 mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Penugasan {{ $user->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                <span class="text-slate-400">&#64;{{ $user->username }}</span>
                — daftar SLS yang saat ini ditugaskan. Hapus dilakukan satu per satu.
            </p>
        </div>
        @if ($kegiatan)
            <a href="{{ route('admin.users.penugasan.tambah', [$user, 'kegiatan' => $kegiatan->id]) }}"
               class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition">
                + Tugaskan SLS baru
            </a>
        @endif
    </div>

    @if (session('success'))
        <div class="mb-5 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 p-3 bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl">{{ $errors->first() }}</div>
    @endif

    @if (! $kegiatan)
        <div class="p-8 bg-white rounded-2xl ring-1 ring-slate-200/70 text-center text-sm text-slate-500">
            Belum ada kegiatan. Buat kegiatan dulu di menu Kegiatan.
        </div>
    @else
        <div class="flex flex-wrap items-end gap-3 mb-4">
            <div class="w-72">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kegiatan</label>
                <select onchange="window.location = this.value"
                    class="w-full text-sm rounded-xl border-slate-200 bg-white focus:ring-teal-500 focus:border-teal-500">
                    @foreach ($kegiatanList as $k)
                        <option value="{{ route('admin.users.penugasan', [$user, 'kegiatan' => $k->id]) }}" @selected($k->id === $kegiatan->id)>
                            {{ $k->nama_kegiatan }}@if (($ringkasan[$k->id] ?? 0) > 0) ({{ $ringkasan[$k->id] }} SLS)@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="kegiatan" value="{{ $kegiatan->id }}">
                <input type="search" name="q" value="{{ $q }}" placeholder="Cari ID SLS, nama, atau kelurahan…"
                    class="text-sm rounded-xl border-slate-200 bg-white focus:ring-teal-500 focus:border-teal-500 w-72">
                <button class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">Cari</button>
            </form>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">SLS</th>
                        <th class="px-5 py-3 text-left font-semibold">Wilayah</th>
                        <th class="px-5 py-3 text-left font-semibold">Polygon</th>
                        <th class="px-5 py-3 text-left font-semibold">Titik</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftar as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <p class="font-semibold text-slate-800">{{ $r->id_sls }}</p>
                                <p class="text-xs text-slate-400">{{ $r->nama_sls }}</p>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ $r->desa_kelurahan }}<br>{{ $r->kecamatan }}</td>
                            <td class="px-5 py-3">
                                @if ($r->punya_polygon)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700">Ada</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700">Belum ada</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-600">
                                {{ $r->jumlah_titik }} titik<br>
                                <span class="text-slate-400">{{ $r->titik_mitra }} di-tag {{ $user->name }}</span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <form method="POST" action="{{ route('admin.users.penugasan.hapus', [$user, $r->sls_id]) }}"
                                      onsubmit="return confirm({{ Js::from('Cabut SLS ' . $r->id_sls . ' dari ' . $user->name . '? Titik yang sudah di-tag tetap tersimpan.') }})">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="kegiatan_id" value="{{ $kegiatan->id }}">
                                    <button type="submit" class="p-1.5 rounded-lg text-red-500 hover:bg-red-50" title="Cabut penugasan SLS ini">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">
                                @if ($q !== '')
                                    Tidak ada SLS yang cocok dengan pencarian.
                                @else
                                    Belum ada SLS yang ditugaskan ke {{ $user->name }} pada kegiatan ini.
                                    <a href="{{ route('admin.users.penugasan.tambah', [$user, 'kegiatan' => $kegiatan->id]) }}" class="text-teal-700 font-semibold hover:underline">Tugaskan SLS</a>.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($daftar->hasPages())
            <div class="mt-4">{{ $daftar->links() }}</div>
        @endif
    @endif
</div>
</x-app-layout>
