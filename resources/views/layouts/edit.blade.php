<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Edit Kegiatan</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <form method="POST" action="{{ route('kegiatan.update', $kegiatan) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    @include('kegiatan._form')

                    <div class="flex items-center gap-2 pt-2">
                        <button type="submit"
                            class="px-5 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition">
                            Simpan perubahan
                        </button>
                        <a href="{{ route('kegiatan.show', $kegiatan) }}"
                            class="px-5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-semibold transition">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
