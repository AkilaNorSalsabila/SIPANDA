<x-app-layout>
<div class="p-4 sm:p-6 lg:p-8 max-w-6xl" x-data="{ tambah: {{ $errors->has('username') || $errors->has('password') || $errors->has('email') || $errors->has('name') || $errors->has('role') ? 'true' : 'false' }} }">

    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manajemen Akun</h1>
            <p class="text-sm text-slate-500 mt-1">
                Setujui pendaftaran, tambah akun, atur peran (Viewer / Admin), dan tugaskan mitra ke SLS.
            </p>
        </div>
        <button type="button" @click="tambah = true"
            class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold shadow-sm transition w-fit">
            + Tambah Akun
        </button>
    </div>

    @if (session('success'))
        <div class="mb-5 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any() && ! $errors->hasAny(['username', 'password', 'email', 'name', 'role', 'phone']))
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
        $badgeRole = [
            'admin'  => 'bg-violet-50 text-violet-700 ring-violet-200',
            'viewer' => 'bg-sky-50 text-sky-700 ring-sky-200',
        ];
    @endphp

    <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 shadow-sm">

        {{-- Tab + filter peran + pencarian --}}
        <div class="p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 border-b border-slate-100">
            <div class="flex flex-wrap gap-1 bg-slate-100 p-1 rounded-xl w-fit">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('admin.users.index', array_filter(['status' => $key, 'role' => $role])) }}"
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

            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <select name="role" onchange="this.form.submit()"
                    class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500">
                    <option value="">Semua peran</option>
                    @foreach (\App\Models\User::ROLE as $kode => $nama)
                        <option value="{{ $kode }}" @selected($role === $kode)>{{ $nama }}</option>
                    @endforeach
                </select>
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, username, email…"
                    class="w-full sm:w-64 px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm
                           focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 transition">
                <button class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold transition">Cari</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-[11px] uppercase tracking-wide text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Pengguna</th>
                        <th class="px-5 py-3 font-semibold">Peran</th>
                        <th class="px-5 py-3 font-semibold">Penugasan SLS</th>
                        <th class="px-5 py-3 font-semibold">Mendaftar</th>
                        <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        @php $diriSendiri = $user->id === auth()->id(); @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-slate-800">
                                    {{ $user->name }}
                                    @if ($diriSendiri) <span class="ml-1 text-[10px] font-medium text-slate-400">(Anda)</span> @endif
                                </div>
                                <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                <div class="text-xs text-slate-400">&#64;{{ $user->username }}@if ($user->phone) · {{ $user->phone }}@endif</div>
                            </td>

                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if ($status === 'approved')
                                    {{-- Ubah peran langsung dari daftar --}}
                                    <form method="POST" action="{{ route('admin.users.role', $user) }}">
                                        @csrf @method('PATCH')
                                        <select name="role" onchange="this.form.submit()" @disabled($diriSendiri)
                                            title="{{ $diriSendiri ? 'Anda tidak dapat mengubah peran akun sendiri' : 'Ubah peran' }}"
                                            class="text-xs font-semibold rounded-lg border-slate-200 bg-slate-50 py-1.5 pl-2.5 pr-7 focus:ring-teal-500 focus:border-teal-500 disabled:opacity-60">
                                            @foreach (\App\Models\User::ROLE as $kode => $nama)
                                                <option value="{{ $kode }}" @selected($user->role === $kode)>{{ $nama }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset {{ $badgeRole[$user->role] ?? 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                        {{ $user->labelRole() }}
                                    </span>
                                    @if ($status === 'pending' && $user->role_diminta === 'admin')
                                        <div class="mt-1 text-[11px] font-semibold text-amber-600">Meminta peran Admin</div>
                                    @endif
                                @endif
                            </td>

                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if ($user->role === 'admin')
                                    <span class="text-xs text-slate-400">Semua SLS</span>
                                @elseif ($status === 'approved')
                                    <a href="{{ route('admin.users.penugasan', $user) }}"
                                       class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $user->penugasan_count ? 'text-teal-700' : 'text-amber-600' }} hover:underline">
                                        {{ $user->penugasan_count ? $user->penugasan_count . ' SLS' : 'Belum ditugaskan' }}
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>

                            <td class="px-5 py-3.5 text-slate-500 whitespace-nowrap">{{ $user->created_at?->translatedFormat('d M Y, H:i') }}</td>

                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="flex justify-end items-center gap-2">

                                    {{-- Disetujui (viewer): atur penugasan SLS --}}
                                    @if ($status === 'approved' && $user->role !== 'admin')
                                        <a href="{{ route('admin.users.penugasan', $user) }}"
                                           class="px-3 py-1.5 rounded-lg bg-white border border-teal-200 text-teal-700 hover:bg-teal-50 text-xs font-semibold transition">
                                            Penugasan SLS
                                        </a>
                                    @endif

                                    {{-- Menunggu / Ditolak / Nonaktif: setujui + pilih peran --}}
                                    @if (in_array($status, ['pending', 'rejected', 'nonaktif']))
                                        <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="flex items-center gap-2">
                                            @csrf @method('PATCH')
                                            <select name="role" class="text-xs rounded-lg border-slate-200 bg-slate-50 py-1.5 pl-2.5 pr-7 focus:ring-teal-500 focus:border-teal-500">
                                                @foreach (\App\Models\User::ROLE as $kode => $nama)
                                                    <option value="{{ $kode }}" @selected(($user->role ?: 'viewer') === $kode)>{{ $nama }}</option>
                                                @endforeach
                                            </select>
                                            <button class="px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">
                                                {{ $status === 'nonaktif' ? 'Aktifkan kembali' : 'Setujui' }}
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

                                    {{-- Disetujui: bisa dinonaktifkan (bukan akun sendiri) --}}
                                    @if ($status === 'approved' && ! $diriSendiri)
                                        <form method="POST" action="{{ route('admin.users.deactivate', $user) }}"
                                              onsubmit="return confirm('Nonaktifkan akun {{ e($user->name) }}?')">
                                            @csrf @method('PATCH')
                                            <button class="px-3 py-1.5 rounded-lg bg-white border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold transition">
                                                Nonaktifkan
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400 text-sm">
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

    {{-- ================= MODAL TAMBAH AKUN ================= --}}
    <div x-show="tambah" x-cloak style="display:none" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @keydown.escape.window="tambah = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.outside="tambah = false">
            <div class="flex items-start justify-between px-6 pt-5 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Tambah Akun</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Akun yang dibuat admin langsung aktif, tanpa menunggu persetujuan.</p>
                </div>
                <button type="button" @click="tambah = false" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
            </div>

            @if ($errors->hasAny(['username', 'password', 'email', 'name', 'role', 'phone']))
                <div class="mx-6 mb-2 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded-xl">
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.users.store') }}" class="px-6 pb-6 space-y-4">
                @csrf

                <div>
                    <label for="t_name" class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                    <input id="t_name" type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="t_username" class="block text-xs font-semibold text-slate-700 mb-1.5">Username</label>
                        <input id="t_username" type="text" name="username" value="{{ old('username') }}" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500">
                    </div>
                    <div>
                        <label for="t_phone" class="block text-xs font-semibold text-slate-700 mb-1.5">No. Telepon <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input id="t_phone" type="tel" name="phone" value="{{ old('phone') }}"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500">
                    </div>
                </div>

                <div>
                    <label for="t_email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email</label>
                    <input id="t_email" type="email" name="email" value="{{ old('email') }}" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500">
                </div>

                <div>
                    <label for="t_role" class="block text-xs font-semibold text-slate-700 mb-1.5">Peran</label>
                    <select id="t_role" name="role"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500">
                        @foreach (\App\Models\User::ROLE as $kode => $nama)
                            <option value="{{ $kode }}" @selected(old('role', 'viewer') === $kode)>{{ $nama }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Viewer: melihat peta &amp; tagging di SLS yang ditugaskan. Admin: akses penuh, termasuk kelola akun.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="t_password" class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
                        <x-password-input id="t_password" name="password" autocomplete="new-password" />
                    </div>
                    <div>
                        <label for="t_password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5">Konfirmasi Password</label>
                        <x-password-input id="t_password_confirmation" name="password_confirmation" autocomplete="new-password" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="tambah = false"
                        class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold transition">Batal</button>
                    <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition">Buat Akun</button>
                </div>
            </form>
        </div>
    </div>

</div>
</x-app-layout>
