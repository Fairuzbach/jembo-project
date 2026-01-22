<div class="bg-gradient-to-r from-white to-slate-50 p-6 rounded-lg shadow-lg border-l-4 border-slate-900 mb-6">
    <form action="{{ route('ga.dashboard') }}" method="GET" class="space-y-4">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-4">
            <div class="bg-slate-900 p-2 rounded-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest">Filter Data</h3>
                <p class="text-xs text-slate-500">Pilih rentang tanggal untuk melihat data</p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row items-end gap-4">
            {{-- Input Tanggal Mulai --}}
            <div class="w-full md:flex-1">
                <label for="start_date" class="block text-xs font-bold text-slate-700 uppercase tracking-widest mb-2">
                    📅 Tanggal Mulai
                </label>
                <input type="date" name="start_date" id="start_date"
                    value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}"
                    class="w-full border-2 border-slate-300 rounded-lg shadow-sm focus:border-yellow-400 focus:ring-2 focus:ring-yellow-200 focus:ring-opacity-50 text-sm py-2.5 px-3 transition-all duration-200"
                    required>
            </div>

            {{-- Input Tanggal Akhir --}}
            <div class="w-full md:flex-1">
                <label for="end_date" class="block text-xs font-bold text-slate-700 uppercase tracking-widest mb-2">
                    📅 Tanggal Akhir
                </label>
                <input type="date" name="end_date" id="end_date"
                    value="{{ request('end_date', now()->format('Y-m-d')) }}"
                    class="w-full border-2 border-slate-300 rounded-lg shadow-sm focus:border-yellow-400 focus:ring-2 focus:ring-yellow-200 focus:ring-opacity-50 text-sm py-2.5 px-3 transition-all duration-200"
                    required>
            </div>

            {{-- Tombol Actions --}}
            <div class="flex gap-2 w-full md:w-auto">
                {{-- Tombol Filter --}}
                <button type="submit"
                    class="flex-1 md:flex-initial bg-gradient-to-r from-slate-900 to-slate-700 text-white hover:from-slate-800 hover:to-slate-600 font-bold py-2.5 px-6 rounded-lg text-sm uppercase tracking-wide transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Filter
                </button>

                {{-- Tombol Reset --}}
                @if (request('start_date') || request('end_date'))
                    <a href="{{ route('ga.dashboard') }}"
                        class="flex-1 md:flex-initial bg-white text-red-600 hover:bg-red-50 border-2 border-red-300 hover:border-red-400 font-bold py-2.5 px-4 rounded-lg text-sm uppercase tracking-wide transition-all duration-200 shadow-sm hover:shadow-md flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset
                    </a>
                @endif

                {{-- Quick Filter Buttons --}}
                <div class="hidden md:flex gap-2 ml-2">
                    <button type="button" onclick="setThisMonth()"
                        class="bg-white hover:bg-slate-50 border-2 border-slate-300 text-slate-700 font-semibold py-2 px-3 rounded-lg text-xs transition-all duration-200 shadow-sm hover:shadow">
                        Bulan Ini
                    </button>
                    <button type="button" onclick="setThisWeek()"
                        class="bg-white hover:bg-slate-50 border-2 border-slate-300 text-slate-700 font-semibold py-2 px-3 rounded-lg text-xs transition-all duration-200 shadow-sm hover:shadow">
                        Minggu Ini
                    </button>
                    <button type="button" onclick="setToday()"
                        class="bg-white hover:bg-slate-50 border-2 border-slate-300 text-slate-700 font-semibold py-2 px-3 rounded-lg text-xs transition-all duration-200 shadow-sm hover:shadow">
                        Hari Ini
                    </button>
                </div>
            </div>
        </div>

        {{-- Info tanggal yang dipilih --}}
        @if (request('start_date') && request('end_date'))
            <div class="bg-blue-50 border-l-4 border-blue-400 p-3 rounded-r-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-blue-700 font-semibold">
                        Menampilkan data dari
                        <span
                            class="font-bold">{{ \Carbon\Carbon::parse(request('start_date'))->format('d M Y') }}</span>
                        sampai
                        <span class="font-bold">{{ \Carbon\Carbon::parse(request('end_date'))->format('d M Y') }}</span>
                        <span
                            class="text-blue-600">({{ \Carbon\Carbon::parse(request('start_date'))->diffInDays(request('end_date')) + 1 }}
                            hari)</span>
                    </p>
                </div>
            </div>
        @endif
    </form>
</div>

<script>
    // Quick filter functions
    function setThisMonth() {
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);

        document.getElementById('start_date').value = formatDate(firstDay);
        document.getElementById('end_date').value = formatDate(lastDay);
    }

    function setThisWeek() {
        const now = new Date();
        const firstDay = new Date(now.setDate(now.getDate() - now.getDay()));
        const lastDay = new Date(now.setDate(now.getDate() - now.getDay() + 6));

        document.getElementById('start_date').value = formatDate(firstDay);
        document.getElementById('end_date').value = formatDate(lastDay);
    }

    function setToday() {
        const now = new Date();
        document.getElementById('start_date').value = formatDate(now);
        document.getElementById('end_date').value = formatDate(now);
    }

    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Validasi tanggal
    document.getElementById('end_date').addEventListener('change', function() {
        const startDate = new Date(document.getElementById('start_date').value);
        const endDate = new Date(this.value);

        if (endDate < startDate) {
            alert('⚠️ Tanggal akhir tidak boleh lebih kecil dari tanggal mulai!');
            this.value = document.getElementById('start_date').value;
        }
    });

    document.getElementById('start_date').addEventListener('change', function() {
        const startDate = new Date(this.value);
        const endDate = new Date(document.getElementById('end_date').value);

        if (endDate < startDate) {
            document.getElementById('end_date').value = this.value;
        }
    });
</script>
