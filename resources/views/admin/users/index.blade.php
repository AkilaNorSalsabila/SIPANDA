<x-app-layout>
<div class="p-4 sm:p-6 lg:p-8 max-w-6xl">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manajemen Akun</h1>
        <p class="text-sm text-slate-500 mt-1">
            Setujui atau tolak akun yang mendaftar. Akun yang disetujui hanya dapat melihat peta.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-5 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 p-3 bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl">
            {{ $errors->first() }}
        </div>
    @endif

    @php
        $tabs = [
            'pending'  => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'nonaktif' => 'Nonaktif',
        ];
    @endphp

    <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 shadow-sm">

        {{-- Tab + pencarian --}}
        <div class="p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 border-b border-slate-100">
            <div class="flex flex-wrap gap-1 bg-slate-100 p-1 rounded-xl w-fit">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('admin.users.index', ['status' => $key]) }}"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition
                              {{ $status === $key ? 'bg-white text-teal-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                        {{ $label }}
                        <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-full
                                     {{ $status === $key ? 'bg-teal-100 text-teal-700' : 'bg-slate-200 text-slate-600' }}">
                            {{ $counts[$key] ?? 0 }}
                        </span>
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, username, email…"
                    class="w-full sm:w-64 px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm
                           focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 transition">
                <button class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold transition">Cari</button>
            </form>
        </div>

        {{-- Tabel (nama, email, dan username digabung supaya muat tanpa scroll ke samping) --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-[11px] uppercase tracking-wide text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Pengguna</th>
                        <th class="px-5 py-3 font-semibold">Telepon</th>
                        <th class="px-5 py-3 font-semibold">Mendaftar</th>
                        <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-slate-800">{{ $user->name }}</div>
                                <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                <div class="text-xs text-slate-400">&#64;{{ $user->username }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600 whitespace-nowrap">{{ $user->phone ?: '—' }}</td>
                            <td class="px-5 py-3.5 text-slate-500 whitespace-nowrap">{{ $user->created_at?->translatedFormat('d M Y, H:i') }}</td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="flex justify-end items-center gap-2">

                                    {{-- Menunggu / Ditolak: setujui --}}
                                    @if (in_array($status, ['pending', 'rejected']))
                                        <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                            @csrf @method('PATCH')
                                            <button class="px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">
                                                Setujui
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Menunggu: bisa ditolak --}}
                                    @if ($status === 'pending')
                                        <form method="POST" action="{{ route('admin.users.reject', $user) }}"
                                              onsubmit="return confirm('Tolak pendaftaran {{ e($user->name) }}?')">
                                            @csrf @method('PATCH')
                                            <button class="px-3 py-1.5 rounded-lg bg-white border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold transition">
                                                Tolak
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Disetujui: bisa dinonaktifkan --}}
                                    @if ($status === 'approved')
                                        <form method="POST" action="{{ route('admin.users.deactivate', $user) }}"
                                              onsubmit="return confirm('Nonaktifkan akun {{ e($user->name) }}?')">
                                            @csrf @method('PATCH')
                                            <button class="px-3 py-1.5 rounded-lg bg-white border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold transition">
                                                Nonaktifkan
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Nonaktif: aktifkan kembali --}}
                                    @if ($status === 'nonaktif')
                                        <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                            @csrf @method('PATCH')
                                            <button class="px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">
                                                Aktifkan kembali
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-slate-400 text-sm">
                                Tidak ada akun pada daftar ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
</x-app-layout>

