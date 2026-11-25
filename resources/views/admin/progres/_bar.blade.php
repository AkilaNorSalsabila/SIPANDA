{{-- Bar progres. Lebar lewat style inline supaya tidak bergantung pada build Tailwind. --}}
<div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
    <div class="h-full rounded-full {{ $persen >= 100 ? 'bg-emerald-500' : 'bg-teal-500' }}" style="width: {{ max(0, min(100, $persen)) }}%"></div>
</div>
