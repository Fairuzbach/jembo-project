@props(['wo'])

<div class="flex flex-col gap-2">
    {{-- Logic Warna Status --}}
    @php
        $st = $wo->status;
        $cls = match ($st) {
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'in_progress' => 'bg-blue-50 text-blue-700 border-blue-200',
            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
            'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    @endphp

    {{-- Badge Status --}}
    <div>
        <span
            class="inline-flex items-center px-2.5 py-1 rounded-md border {{ $cls }} text-[10px] font-bold uppercase tracking-wide">
            {{ str_replace('_', ' ', $st) }}
        </span>
    </div>

    {{-- Avatar Teknisi --}}
    @if ($wo->technicians->count() > 0)
        <div class="flex -space-x-1 overflow-hidden">
            @foreach ($wo->technicians->take(3) as $tech)
                <div class="inline-flex h-6 w-6 rounded-full ring-2 ring-white bg-slate-200 items-center justify-center text-[9px] font-bold text-slate-600 hover:z-10 transition-transform hover:scale-110"
                    title="{{ $tech->name }}">
                    {{ substr($tech->name, 0, 1) }}
                </div>
            @endforeach
            @if ($wo->technicians->count() > 3)
                <div
                    class="inline-flex h-6 w-6 rounded-full ring-2 ring-white bg-slate-100 items-center justify-center text-[9px] font-bold text-slate-500">
                    +{{ $wo->technicians->count() - 3 }}
                </div>
            @endif
        </div>
    @endif
</div>
