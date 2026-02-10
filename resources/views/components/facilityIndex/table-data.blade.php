@props(['workOrders'])

<div
    class="bg-white rounded-2xl shadow-md border border-slate-100 overflow-hidden hover:shadow-xl transition-shadow duration-300">

    <div class="hidden sm:block overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr
                    class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600 font-extrabold">
                    <th class="px-6 py-5 w-10 text-center">
                        <input type="checkbox" @change="toggleSelectAll()"
                            :checked="selectedTickets.length === pageIds.length && pageIds.length > 0"
                            class="rounded border-slate-300 text-[#1E3A5F] focus:ring-[#1E3A5F] w-4 h-4 cursor-pointer">
                    </th>
                    <th class="px-6 py-5">Tiket</th>
                    <th class="px-6 py-5">Pemohon</th>
                    <th class="px-6 py-5">Lokasi</th>
                    <th class="px-6 py-5">Kategori Pekerjaan</th>
                    <th class="px-6 py-5">Status & PIC</th>
                    <th class="px-6 py-5 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($workOrders as $wo)
                    <tr class="hover:bg-slate-50/50 transition-all duration-150 group">
                        {{-- Checkbox --}}
                        <td class="px-6 py-5 text-center align-top pt-6">
                            <input type="checkbox" value="{{ $wo->id }}" x-model="selectedTickets"
                                class="rounded border-slate-300 text-[#1E3A5F] focus:ring-[#1E3A5F] w-4 h-4 cursor-pointer transition-all">
                        </td>

                        {{-- Tiket Info --}}
                        <td class="px-6 py-5 align-top">
                            <div class="flex items-start gap-3">
                                <div
                                    class="h-10 w-10 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-[10px] group-hover:bg-[#1E3A5F] group-hover:text-white transition-all duration-200">
                                    WO
                                </div>
                                <div>
                                    <div class="font-bold text-slate-700 text-sm">{{ $wo->ticket_num }}</div>
                                    <div class="text-[11px] text-slate-400 mt-1">
                                        {{ $wo->report_date ? \Carbon\Carbon::parse($wo->report_date)->translatedFormat('d M Y') : '-' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Pemohon --}}
                        <td class="px-6 py-5 align-top">
                            <div class="text-sm font-bold text-slate-700">{{ $wo->requester_name }}</div>
                        </td>

                        {{-- Lokasi --}}
                        <td class="px-6 py-5 align-top">
                            <div class="text-sm font-bold text-slate-700 mb-1">{{ $wo->plant }}</div>
                            @if ($wo->machine)
                                <span
                                    class="inline-block px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-[10px] font-bold text-slate-600">
                                    {{ $wo->machine->name }}
                                </span>
                            @endif
                        </td>

                        {{-- Kategori --}}
                        <td class="px-6 py-5 align-top">
                            <span
                                class="inline-block px-3 py-1 rounded-full bg-slate-50 border border-slate-200 text-[11px] font-bold text-slate-600">
                                {{ $wo->category }}
                            </span>
                            <div class="text-[11px] text-slate-400 mt-2 max-w-[300px] truncate"
                                title="{{ $wo->description }}">
                                {{ $wo->description }}
                            </div>
                        </td>

                        {{-- Status & PIC --}}
                        <td class="px-6 py-5 align-top">
                            @include('components.facilityIndex.status-badge-desktop', ['wo' => $wo])
                        </td>

                        {{-- Action Buttons --}}
                        <td class="px-6 py-5 align-top text-right">
                            @include('components.facilityIndex.action-buttons-desktop', ['wo' => $wo])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-16 text-center">
                            @include('components.facilityIndex.empty-state')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ========================================================= --}}
    {{-- TAMPILAN MOBILE (CARD) --}}
    {{-- Visible di Mobile (block), Hidden di Desktop (sm:hidden) --}}
    {{-- ========================================================= --}}
    <div class="block sm:hidden divide-y divide-slate-100">
        @forelse($workOrders as $wo)
            <div class="p-4 hover:bg-slate-50 transition-colors relative">

                {{-- Header Card: Tiket & Status --}}
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" value="{{ $wo->id }}" x-model="selectedTickets"
                            class="rounded border-slate-300 text-[#1E3A5F] focus:ring-[#1E3A5F] w-4 h-4 cursor-pointer">
                        <div>
                            <div class="text-sm font-bold text-slate-800">{{ $wo->ticket_num }}</div>
                            <div class="text-[10px] text-slate-400">
                                {{ $wo->report_date ? \Carbon\Carbon::parse($wo->report_date)->translatedFormat('d M Y') : '-' }}
                            </div>
                        </div>
                    </div>

                    {{-- Status Badge Mobile --}}
                    @php
                        $st = $wo->status;
                        $cls = match ($st) {
                            'completed' => 'bg-emerald-100 text-emerald-800',
                            'in_progress' => 'bg-blue-100 text-blue-800',
                            'pending' => 'bg-amber-100 text-amber-800',
                            'cancelled' => 'bg-rose-100 text-rose-800',
                            default => 'bg-slate-100 text-slate-700',
                        };
                    @endphp
                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase {{ $cls }}">
                        {{ str_replace('_', ' ', $st) }}
                    </span>
                </div>

                {{-- Body Card: Detail Info --}}
                <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                    <div>
                        <p class="text-slate-400 text-[10px] uppercase">Pemohon</p>
                        <p class="font-semibold text-slate-700 truncate">{{ $wo->requester_name }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-[10px] uppercase">Lokasi</p>
                        <p class="font-semibold text-slate-700 truncate">{{ $wo->plant }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-slate-400 text-[10px] uppercase">Deskripsi</p>
                        <p class="text-slate-600 line-clamp-2">{{ $wo->description }}</p>
                    </div>
                </div>

                {{-- Footer Card: Actions --}}
                <div class="flex justify-between items-center pt-3 border-t border-slate-100">
                    {{-- View Detail --}}
                    <button @click="openDetail({{ $wo->id }})"
                        class="text-blue-600 text-xs font-bold hover:underline flex items-center gap-1">
                        Lihat Detail →
                    </button>

                    {{-- Action Buttons (Simplified for Mobile) --}}
                    <div class="flex gap-2">
                        @include('components.facilityIndex.action-buttons-desktop', [
                            'wo' => $wo,
                            'isMobile' => true,
                        ])
                    </div>
                </div>
            </div>
        @empty
            <div class="py-10 text-center">
                @include('components.facilityIndex.empty-state')
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
        {{ $workOrders->links() }}
    </div>
</div>

{{-- 
    HELPER COMPONENTS (Agar kode utama tidak kepanjangan)
    Anda bisa memisahkannya ke file blade tersendiri jika mau, 
    atau taruh di bawah ini dalam satu file.
--}}

@php
    function getStatusClass($st)
    {
        return match ($st) {
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'in_progress' => 'bg-blue-50 text-blue-700 border-blue-200',
            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
            'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }
@endphp

{{-- KODE PARTIALS UNTUK ACTION BUTTON & STATUS (Di-include manual karena ini satu file) --}}
{{-- PENTING: Paste kode PHP Logic $showActionButtons di sini atau di Controller agar bisa dipakai --}}
