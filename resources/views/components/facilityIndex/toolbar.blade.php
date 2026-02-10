@props([
    'listPlants' => [],
    'selectedTickets' => [],
])

<div
    class="bg-white rounded-[1.5rem] shadow-md border border-slate-100 p-6 hover:shadow-lg transition-shadow duration-300">
    <form action="{{ route('fh.index') }}" method="GET"
        class="flex flex-col xl:flex-row gap-4 items-end xl:items-center justify-between">

        {{-- Filter Group --}}
        <div class="flex flex-col lg:flex-row gap-3 w-full xl:w-auto flex-1">
            {{-- Search --}}
            <div class="relative w-full lg:w-64 group">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search ticket / requester..."
                    class="w-full pl-11 pr-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-sm font-medium transition duration-200 shadow-sm group-hover:shadow-md">
                <div
                    class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            {{-- Filter Kategori --}}
            <select name="category" onchange="this.form.submit()"
                class="w-full lg:w-48 rounded-xl border border-slate-200 text-sm py-3 px-4 bg-slate-50 font-medium text-slate-600 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 cursor-pointer hover:bg-white shadow-sm hover:shadow-md transition-all duration-200 appearance-none bg-no-repeat bg-right pr-10"
                style="background-image: url('data:image/svg+xml;utf8,<svg class=%22w-4 h-4%22 fill=%22none%22 stroke=%22%2364748b%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M19 9l-7 7-7-7%22></path></svg>'); background-position: right 0.75rem center; background-size: 1.25rem;">
                <option value="">All Category</option>
                <option value="Modifikasi Mesin" {{ request('category') == 'Modifikasi Mesin' ? 'selected' : '' }}>
                    Modifikasi Mesin
                </option>
                <option value="Pemasangan Mesin" {{ request('category') == 'Pemasangan Mesin' ? 'selected' : '' }}>
                    Pemasangan Mesin
                </option>
                <option value="Pembongkaran Mesin" {{ request('category') == 'Pembongkaran Mesin' ? 'selected' : '' }}>
                    Pembongkaran Mesin
                </option>
                <option value="Relokasi Mesin" {{ request('category') == 'Relokasi Mesin' ? 'selected' : '' }}>
                    Relokasi Mesin
                </option>
                <option value="Perbaikan" {{ request('category') == 'Perbaikan' ? 'selected' : '' }}>
                    Perbaikan
                </option>
                <option value="Pembuatan Alat Baru"
                    {{ request('category') == 'Pembuatan Alat Baru' ? 'selected' : '' }}>
                    Pembuatan Alat Baru
                </option>
                <option value="Rakit Steel Drum" {{ request('category') == 'Rakit Steel Drum' ? 'selected' : '' }}>
                    Rakit Steel Drum
                </option>
            </select>

            {{-- Filter Status --}}
            <select name="status" onchange="this.form.submit()"
                class="w-full lg:w-40 rounded-xl border border-slate-200 text-sm py-3 px-4 bg-slate-50 font-medium text-slate-600 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 cursor-pointer hover:bg-white shadow-sm hover:shadow-md transition-all duration-200 appearance-none bg-no-repeat bg-right pr-10"
                style="background-image: url('data:image/svg+xml;utf8,<svg class=%22w-4 h-4%22 fill=%22none%22 stroke=%22%2364748b%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M19 9l-7 7-7-7%22></path></svg>'); background-position: right 0.75rem center; background-size: 1.25rem;">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress
                </option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
            </select>

            {{-- Filter Plant --}}
            <select name="plant_id" onchange="this.form.submit()"
                class="w-full lg:w-40 rounded-xl border border-slate-200 text-sm py-3 px-4 bg-slate-50 font-medium text-slate-600 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 cursor-pointer hover:bg-white shadow-sm hover:shadow-md transition-all duration-200 appearance-none bg-no-repeat bg-right pr-10"
                style="background-image: url('data:image/svg+xml;utf8,<svg class=%22w-4 h-4%22 fill=%22none%22 stroke=%22%2364748b%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M19 9l-7 7-7-7%22></path></svg>'); background-position: right 0.75rem center; background-size: 1.25rem;">
                <option value="">All Plants</option>
                @foreach ($listPlants as $plant)
                    <option value="{{ $plant->id }}" {{ request('plant_id') == $plant->id ? 'selected' : '' }}>
                        {{ $plant->name }}
                    </option>
                @endforeach
            </select>

            {{-- Tombol Reset Filter --}}
            <a href="{{ route('fh.index') }}"
                class="px-5 py-3 rounded-xl border border-slate-200 text-slate-600 text-sm font-bold hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 flex items-center justify-center gap-2 transition duration-200 shadow-sm hover:shadow-md bg-white"
                title="Reset All Filters">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                </svg>
                Reset
            </a>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap gap-3 w-full lg:w-auto">
            {{-- Tombol Export --}}
            <button type="button" @click="submitExport()"
                class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-bold hover:bg-slate-50 flex items-center gap-2 transition hover:text-green-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Export
                <span x-show="selectedTickets.length > 0" class="text-xs bg-slate-200 px-1.5 rounded-full ml-1"
                    x-text="selectedTickets.length"></span>
            </button>

            {{-- Tombol New Ticket - PAKAI STYLE YANG SAMA SEPERTI GA --}}
            <button type="button" @click="$dispatch('open-create-modal')"
                class="flex items-center justify-center gap-2 bg-[#1E3A5F] hover:bg-[#162c46] text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all transform active:scale-95 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Ticket
            </button>
        </div>
    </form>
</div>
