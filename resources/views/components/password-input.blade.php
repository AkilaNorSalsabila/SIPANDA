@props(['id', 'name', 'autocomplete' => 'current-password'])

<div class="relative">
    <input id="{{ $id }}" type="password" name="{{ $name }}" required autocomplete="{{ $autocomplete }}"
        {{ $attributes->merge(['class' => 'block w-full pl-4 pr-12 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800
                                focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 transition']) }}>
    <button type="button" data-toggle-password="{{ $id }}" aria-label="Tampilkan / sembunyikan password"
        style="position:absolute; top:50%; right:10px; transform:translateY(-50%);"
        class="flex items-center justify-center p-1 text-slate-400 hover:text-teal-600 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="block w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    </button>
</div>