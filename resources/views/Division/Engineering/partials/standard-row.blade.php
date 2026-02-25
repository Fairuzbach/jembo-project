<tr class="hover:bg-slate-50/80 transition">
    <td class="p-4 border-r border-slate-100">
        <div class="font-extrabold text-slate-800">{{ $std->nama_mesin }}</div>
        <div class="text-[10px] font-bold text-slate-400 mt-1"><span
                class="bg-slate-200 px-1.5 py-0.5 rounded text-slate-600">{{ $std->kode_mesin }}</span></div>
    </td>
    <td class="p-4 text-center border-r border-slate-100">
        @if ($std->proses === 'drawing')
            <span
                class="bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-extrabold px-3 py-1 rounded-full uppercase tracking-wider">Drawing</span>
        @else
            <span
                class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-extrabold px-3 py-1 rounded-full uppercase tracking-wider">Annealing</span>
        @endif
    </td>
    <td class="p-4 border-r border-slate-100">
        <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold text-slate-400 w-12">TIPE</span>
            <span class="font-bold text-slate-800 text-xs">{{ $std->std_tipe ?? '-' }}</span>
        </div>
        <div class="flex items-center gap-2 mt-1">
            <span class="text-[10px] font-bold text-slate-400 w-12">SUPP</span>
            <span class="font-medium text-slate-600 text-xs">{{ $std->std_supplier ?? '-' }}</span>
        </div>
        <div class="flex items-center gap-2 mt-1">
            <span class="text-[10px] font-bold text-slate-400 w-12">WARNA</span>
            <span class="font-medium text-slate-600 text-xs">{{ $std->std_warna ?? '-' }}</span>
        </div>
    </td>
    <td class="p-4 border-r border-slate-100">
        <div class="grid grid-cols-1 gap-1.5">
            <div class="flex items-center justify-between bg-white border border-slate-200 px-2 py-1 rounded">
                <span class="text-[10px] font-bold text-slate-500 uppercase">Konsentrasi</span>
                <span class="text-xs font-bold text-blue-600">{{ $std->std_konsentrasi ?? '-' }}</span>
            </div>
            <div class="flex items-center justify-between bg-white border border-slate-200 px-2 py-1 rounded">
                <span class="text-[10px] font-bold text-slate-500 uppercase">pH Level</span>
                <span class="text-xs font-bold text-emerald-600">{{ $std->std_ph ?? '-' }}</span>
            </div>
            <div class="flex items-center justify-between bg-white border border-slate-200 px-2 py-1 rounded">
                <span class="text-[10px] font-bold text-slate-500 uppercase">Temperatur</span>
                <span class="text-xs font-bold text-orange-600">{{ $std->std_temp ?? '-' }}</span>
            </div>
        </div>
    </td>
    <td class="p-4 text-center align-middle">
        <button @click="openModal({{ json_encode($std) }})"
            class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-[11px] font-bold transition shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                </path>
            </svg>
            Ubah Nilai
        </button>
    </td>
</tr>
