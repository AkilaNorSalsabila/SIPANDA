@if ($t->di_luar_sls === true)
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200 whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Di luar SLS
    </span>
@elseif ($t->di_luar_sls === false)
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200 whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Di dalam SLS
    </span>
@else
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-500 ring-1 ring-inset ring-slate-200 whitespace-nowrap">
        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Belum bisa dicek
    </span>
@endif