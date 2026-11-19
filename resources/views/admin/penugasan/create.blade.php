<x-app-layout>
<div class="p-4 sm:p-6 lg:p-8 max-w-5xl">

    <a href="{{ route('admin.users.penugasan', array_filter([$user, 'kegiatan' => $kegiatan?->id])) }}" class="text-xs font-semibold text-teal-700 hover:underline">&larr; Kembali ke daftar penugasan {{ $user->name }}</a>

    <div class="mt-2 mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tugaskan SLS Baru</h1>
        <p class="text-sm text-slate-500 mt-1">
            <b class="text-slate-700">{{ $user->name }}</b> <span class="text-slate-400">&#64;{{ $user->username }}</span>
            — centang SLS yang akan ditambahkan. SLS yang sudah ditugaskan ke mitra ini tidak ditampilkan di sini dan tidak akan berubah.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-5 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 p-3 bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl">{{ $errors->first() }}</div>
    @endif

    @if (! $kegiatan)
        <div class="p-8 bg-white rounded-2xl ring-1 ring-slate-200/70 text-center text-sm text-slate-500">
            Belum ada kegiatan. Buat kegiatan dulu di menu Kegiatan, baru tugaskan SLS.
        </div>
    @else
    <form method="POST" action="{{ route('admin.users.penugasan.store', $user) }}" id="formPenugasan"
          class="bg-white rounded-2xl ring-1 ring-slate-200/70 shadow-sm">
        @csrf
        <input type="hidden" name="kegiatan_id" value="{{ $kegiatan->id }}">

        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col lg:flex-row lg:items-end gap-4">
            <div class="lg:w-72">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kegiatan</label>
                {{-- Ganti kegiatan = pindah halaman (perubahan yang belum disimpan tidak terbawa) --}}
                <select id="pilihKegiatan"
                    class="w-full text-sm rounded-xl border-slate-200 bg-slate-50 focus:ring-teal-500 focus:border-teal-500">
                    @foreach ($kegiatanList as $k)
                        <option value="{{ route('admin.users.penugasan.tambah', [$user, 'kegiatan' => $k->id]) }}" @selected($k->id === $kegiatan->id)>
                            {{ $k->nama_kegiatan }}@if (($ringkasan[$k->id] ?? 0) > 0) ({{ $ringkasan[$k->id] }} SLS)@endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Cari SLS / kelurahan</label>
                <input id="cariSls" type="text" placeholder="Ketik ID SLS, nama SLS, atau kelurahan…"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm
                           focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500">
            </div>

            <div class="flex items-center gap-2 text-xs">
                <button type="button" id="pilihTampil" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition">Pilih yang tampil</button>
                <button type="button" id="kosongkan" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition">Kosongkan</button>
            </div>
        </div>

        <div class="divide-y divide-slate-100" id="daftarSls">
            @forelse ($slsGrup as $kecamatan => $perKelurahan)
                <div class="grup-kec">
                    <div class="px-5 py-2.5 bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        Kecamatan {{ $kecamatan }}
                    </div>

                    @foreach ($perKelurahan as $kelurahan => $daftar)
                        <details class="grup-kel border-t border-slate-100">
                            <summary class="px-5 py-3 cursor-pointer flex items-center justify-between gap-3 hover:bg-slate-50/70">
                                <span class="text-sm font-semibold text-slate-800">{{ $kelurahan }}</span>
                                <span class="flex items-center gap-3 text-xs text-slate-500">
                                    <span><b class="hitung-kel text-teal-700">0</b> / {{ $daftar->count() }} dipilih</span>
                                    <label class="flex items-center gap-1.5 font-semibold text-slate-600" onclick="event.stopPropagation()">
                                        <input type="checkbox" class="pilih-kel rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                        Semua
                                    </label>
                                </span>
                            </summary>

                            <ul class="px-5 pb-3 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1">
                                @foreach ($daftar as $s)
                                    <li class="item-sls" data-cari="{{ strtolower($s->id_sls . ' ' . $s->nama_sls . ' ' . $kelurahan . ' ' . $kecamatan) }}">
                                        <label class="flex items-start gap-2 py-1.5 text-sm text-slate-700 cursor-pointer">
                                            <input type="checkbox" name="sls_ids[]" value="{{ $s->id }}"
                                                   class="cek-sls mt-0.5 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                            <span class="leading-snug">
                                                <span class="font-semibold">{{ $s->id_sls }}</span>
                                                <span class="text-slate-500">— {{ $s->nama_sls }}</span>
                                                <span class="block text-[11px] text-slate-400">
                                                    {{ $s->jumlah_titik }} titik
                                                    @unless ($s->punya_polygon) · <span class="text-amber-600">tanpa polygon</span> @endunless
                                                    @if (! empty($mitraLain[$s->id])) · dipegang: {{ implode(', ', $mitraLain[$s->id]) }} @endif
                                                </span>
                                            </span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endforeach
                </div>
            @empty
                <p class="px-5 py-12 text-center text-sm text-slate-400">Tidak ada SLS yang bisa ditambahkan: semua SLS kegiatan ini sudah ditugaskan ke mitra ini, atau kegiatan ini belum punya SLS.
                    Impor batas SLS atau salin SLS dari kegiatan lain di menu <a href="{{ route('import.index') }}" class="text-teal-600 underline">Import Data</a>.</p>
            @endforelse
        </div>

        <div class="sticky bottom-0 p-4 sm:p-5 border-t border-slate-100 bg-white/95 backdrop-blur rounded-b-2xl flex items-center justify-between gap-3">
            <p class="text-sm text-slate-600"><b id="totalDipilih" class="text-teal-700">0</b> SLS akan ditugaskan</p>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition">Tugaskan SLS Terpilih</button>
        </div>
    </form>

    <script>
        (function () {
            const semua = () => [...document.querySelectorAll('.cek-sls')];

            function hitung() {
                document.getElementById('totalDipilih').textContent = semua().filter(c => c.checked).length;

                document.querySelectorAll('.grup-kel').forEach(g => {
                    const cek = [...g.querySelectorAll('.cek-sls')];
                    const n = cek.filter(c => c.checked).length;
                    g.querySelector('.hitung-kel').textContent = n;
                    g.querySelector('.pilih-kel').checked = cek.length > 0 && n === cek.length;
                });
            }

            document.getElementById('daftarSls').addEventListener('change', (e) => {
                if (e.target.classList.contains('pilih-kel')) {
                    const g = e.target.closest('.grup-kel');
                    g.querySelectorAll('.item-sls:not([hidden]) .cek-sls').forEach(c => c.checked = e.target.checked);
                }
                hitung();
            });

            document.getElementById('cariSls').addEventListener('input', (e) => {
                const q = e.target.value.trim().toLowerCase();

                document.querySelectorAll('.item-sls').forEach(li => { li.hidden = q !== '' && ! li.dataset.cari.includes(q); });

                document.querySelectorAll('.grup-kel').forEach(g => {
                    const ada = g.querySelector('.item-sls:not([hidden])');
                    g.hidden = ! ada;
                    if (q !== '' && ada) g.open = true;
                });
                document.querySelectorAll('.grup-kec').forEach(k => { k.hidden = ! k.querySelector('.grup-kel:not([hidden])'); });
            });

            document.getElementById('pilihTampil').addEventListener('click', () => {
                document.querySelectorAll('.item-sls:not([hidden]) .cek-sls').forEach(c => c.checked = true);
                hitung();
            });
            document.getElementById('kosongkan').addEventListener('click', () => {
                semua().forEach(c => c.checked = false);
                hitung();
            });

            document.getElementById('pilihKegiatan').addEventListener('change', (e) => { window.location = e.target.value; });

            hitung();
        })();
    </script>
    @endif

</div>
</x-app-layout>
