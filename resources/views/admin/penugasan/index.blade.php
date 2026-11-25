<x-app-layout>
<div class="p-4 sm:p-6 lg:p-8 max-w-6xl">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Penugasan Mitra</h1>
        <p class="text-sm text-slate-500 mt-1">
            Pilih mitra untuk melihat SLS yang sudah ditugaskan, atau menugaskan SLS baru.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-5 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl">{{ session('success') }}</div>
    @endif

    @if (! $kegiatan)
        <div class="p-8 bg-white rounded-2xl ring-1 ring-slate-200/70 text-center text-sm text-slate-500">
            Belum ada kegiatan. Buat kegiatan dulu di menu Kegiatan, baru tugaskan SLS.
        </div>
    @else
        @if ($slsTanpaPolygon > 0)
            <div class="mb-4 p-3 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-xl leading-relaxed">
                <b>{{ number_format($slsTanpaPolygon) }} dari {{ number_format($totalSls) }} SLS belum punya batas polygon.</b>
                Mitra tidak bisa tagging di SLS tanpa polygon. Impor batas SLS di
                <a href="{{ route('import.index') }}" class="font-semibold underline">Import Data</a>.
            </div>
        @endif

        <form method="GET" class="flex flex-wrap items-center gap-3 mb-4">
            <select name="kegiatan" onchange="this.form.submit()"
                class="text-sm rounded-lg border-slate-200 bg-white focus:ring-teal-500 focus:border-teal-500">
                @foreach ($kegiatanList as $k)
                    <option value="{{ $k->id }}" @selected($k->id === $kegiatan->id)>{{ $k->nama_kegiatan }}</option>
                @endforeach
            </select>
            <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama / username mitra…"
                class="text-sm rounded-lg border-slate-200 bg-white focus:ring-teal-500 focus:border-teal-500 w-64">
            <button class="px-3 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold">Cari</button>
        </form>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
            <div class="bg-white rounded-xl ring-1 ring-slate-200/70 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Mitra aktif</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $mitra->count() }}</p>
            </div>
            <div class="bg-white rounded-xl ring-1 ring-slate-200/70 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total SLS</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($totalSls) }}</p>
            </div>
            <div class="bg-white rounded-xl ring-1 ring-slate-200/70 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">SLS belum ditugaskan (kegiatan ini)</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($slsBelumDitugaskan) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Mitra</th>
                        <th class="px-5 py-3 text-left font-semibold">SLS ditugaskan</th>
                        <th class="px-5 py-3 text-left font-semibold">Titik di-tag</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($mitra as $m)
                        @php $n = (int) ($jumlahSls[$m->id] ?? 0); @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-slate-800">{{ $m->name }}</p>
                                <p class="text-xs text-slate-400">&#64;{{ $m->username }}</p>
                            </td>
                            <td class="px-5 py-3.5">
                                @if ($n)
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 ring-1 ring-inset ring-teal-200">{{ $n }} SLS</span>
                                @else
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200">Belum ditugaskan</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">{{ number_format($titikTagging[$m->id] ?? 0) }}</td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.users.penugasan', [$m, 'kegiatan' => $kegiatan->id]) }}"
                                   class="px-3 py-1.5 rounded-lg bg-white ring-1 ring-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                                    Lihat penugasan
                                </a>
                                <a href="{{ route('admin.users.penugasan.tambah', [$m, 'kegiatan' => $kegiatan->id]) }}"
                                   class="ml-1.5 px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">
                                    + Tugaskan SLS
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">
                                Belum ada mitra (viewer) aktif.
                                <a href="{{ route('admin.users.index', ['status' => 'pending']) }}" class="text-teal-700 font-semibold hover:underline">Setujui pendaftaran di Manajemen Akun</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
</x-app-layout>
