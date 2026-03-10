<x-app-layout>
    <div class="py-12" x-data="standardManager()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Alert Success --}}
            @if (session('success'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg relative flex items-center gap-3 shadow-sm"
                    role="alert">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="block sm:inline text-sm font-bold">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border-t-8 border-indigo-600">

                {{-- HEADER --}}
                <div
                    class="p-5 sm:p-6 bg-white border-b border-slate-200 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-800">Master Standar Compound</h2>
                        <p class="text-sm text-slate-500 mt-1">
                            Manajemen nilai referensi untuk parameter teknis mesin.
                        </p>
                    </div>
                    <a href="{{ route('eng.index') }}"
                        class="text-slate-500 hover:text-indigo-600 flex items-center gap-2 text-sm font-bold transition bg-slate-50 px-4 py-2 rounded-lg border border-slate-200 hover:border-indigo-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali ke Dashboard
                    </a>
                </div>

                @php
                    // Kelompokkan data agar rapi saat ditampilkan
                    $plantAStandards = $standards->where('plant', 'Plant A');

                    // Filter khusus Autowire agar hanya memanggil Mesin Multi Drawing 3 Honta (ID: 55)
                    $autowireStandards = $standards->where('plant', 'Autowire')->where('machine_id', 52);
                @endphp

                <div class="p-0 sm:p-6 bg-slate-50">
                    <form action="{{ route('eng.operator.import') }}" method="POST" enctype="multipart/form-data"
                        class="mb-4 bg-slate-100 p-4 rounded-lg border-2 border-dashed border-slate-300">
                        @csrf
                        <label class="block text-xs font-bold text-slate-700 mb-2">Update Data Operator (Excel)</label>
                        <div class="flex gap-2">
                            <input type="file" name="file_excel" class="text-xs">
                            <button type="submit"
                                class="bg-emerald-600 text-white px-3 py-1 rounded text-xs font-bold">Upload &
                                Import</button>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-2">*Format kolom: nik, nama, plant</p>
                    </form>
                    {{-- TAB NAVIGASI --}}
                    <div class="flex border-b border-slate-200 bg-white sm:rounded-t-lg overflow-x-auto px-4 pt-4">
                        <button @click="activeTab = 'Plant A'"
                            :class="activeTab === 'Plant A' ? 'border-indigo-600 text-indigo-600' :
                                'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                            class="whitespace-nowrap py-3 px-6 border-b-2 font-extrabold text-sm transition-colors">
                            🏭 Plant A ({{ $plantAStandards->count() }} Data)
                        </button>
                        <button @click="activeTab = 'Autowire'"
                            :class="activeTab === 'Autowire' ? 'border-indigo-600 text-indigo-600' :
                                'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                            class="whitespace-nowrap py-3 px-6 border-b-2 font-extrabold text-sm transition-colors">
                            ⚡ Autowire ({{ $autowireStandards->count() }} Data)
                        </button>
                    </div>

                    {{-- TABEL KONTEN --}}
                    <div class="bg-white border border-t-0 border-slate-200 shadow-sm sm:rounded-b-xl overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left whitespace-nowrap">
                                <thead>
                                    <tr class="bg-slate-800 text-white">
                                        <th class="p-4 font-bold text-xs uppercase tracking-wider">Nama Mesin</th>
                                        <th class="p-4 font-bold text-xs uppercase tracking-wider text-center">Proses
                                        </th>
                                        <th class="p-4 font-bold text-xs uppercase tracking-wider">Identitas Cairan</th>
                                        <th class="p-4 font-bold text-xs uppercase tracking-wider text-center">Parameter
                                            Standar</th>
                                        <th class="p-4 font-bold text-xs uppercase tracking-wider text-center">Aksi</th>
                                    </tr>
                                </thead>

                                {{-- TAB BODY: PLANT A --}}
                                <tbody x-show="activeTab === 'Plant A'" class="divide-y divide-slate-200">
                                    @foreach ($plantAStandards as $std)
                                        @include('Division.Engineering.partials.standard-row', [
                                            'std' => $std,
                                        ])
                                    @endforeach
                                </tbody>

                                {{-- TAB BODY: AUTOWIRE --}}
                                <tbody x-show="activeTab === 'Autowire'" style="display: none;"
                                    class="divide-y divide-slate-200">
                                    @foreach ($autowireStandards as $std)
                                        @include('Division.Engineering.partials.standard-row', [
                                            'std' => $std,
                                        ])
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL EDIT STANDAR (Alpine.js) --}}
        <div x-show="isModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="isModalOpen" x-transition.opacity
                    class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" @click="isModalOpen = false">
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="isModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl w-full">

                    <form :action="`/eng/compound/standards/${editData.id}`" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <input type="hidden" name="std_konsentrasi" :value="formattedKonsentrasi">
                            <input type="hidden" name="std_ph" :value="formattedPh">
                            <input type="hidden" name="std_temp" :value="formattedTemp">
                            <div class="flex justify-between items-start mb-5 border-b border-slate-100 pb-4">
                                <div>
                                    <h3 class="text-xl leading-6 font-extrabold text-slate-900">Ubah Standar</h3>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span
                                            class="bg-indigo-100 text-indigo-800 text-[10px] font-bold px-2 py-0.5 rounded uppercase"
                                            x-text="editData.plant"></span>
                                        <span class="text-xs font-bold text-slate-500"
                                            x-text="editData.nama_mesin"></span>
                                        <span class="text-slate-400">•</span>
                                        <span class="text-xs font-bold"
                                            :class="editData.proses === 'drawing' ? 'text-blue-600' : 'text-emerald-600'"
                                            x-text="editData.proses.toUpperCase()"></span>
                                    </div>
                                </div>
                                <button type="button" @click="isModalOpen = false"
                                    class="text-slate-400 hover:text-red-500 transition bg-slate-50 rounded-full p-2">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-5 bg-slate-50 p-4 rounded-lg border border-slate-200">
                                <div>
                                    <label
                                        class="block text-[10px] uppercase tracking-wider font-extrabold text-slate-500 mb-1">Type
                                        Item</label>
                                    <input type="text" name="std_tipe" x-model="editData.std_tipe"
                                        class="w-full rounded border-slate-300 text-sm focus:ring-indigo-500 bg-white font-bold text-slate-800 shadow-sm">
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] uppercase tracking-wider font-extrabold text-slate-500 mb-1">Supplier</label>
                                    <input type="text" name="std_supplier" x-model="editData.std_supplier"
                                        class="w-full rounded border-slate-300 text-sm focus:ring-indigo-500 bg-white font-bold text-slate-800 shadow-sm">
                                </div>
                                <div class="col-span-2">
                                    <label
                                        class="block text-[10px] uppercase tracking-wider font-extrabold text-slate-500 mb-1">Warna</label>
                                    <input type="text" name="std_warna" x-model="editData.std_warna"
                                        class="w-full rounded border-slate-300 text-sm focus:ring-indigo-500 bg-white font-bold text-slate-800 shadow-sm">
                                </div>

                                <div class="col-span-2 border-t border-slate-200 mt-2 pt-4 grid grid-cols-3 gap-3">
                                    <div>
                                        <label
                                            class="block text-[10px] uppercase tracking-wider font-extrabold text-slate-500 mb-1">Konsentrasi
                                            Std <span class="text-blue-600">(%)</span></label>
                                        <div class="flex items-center gap-1">
                                            <input type="number" step="0.01" x-model="konsMin"
                                                class="w-full rounded border-slate-300 text-sm focus:ring-indigo-500 font-bold text-blue-600 text-center shadow-sm px-1"
                                                placeholder="Min">
                                            <span class="text-slate-400 font-bold">-</span>
                                            <input type="number" step="0.01" x-model="konsMax"
                                                class="w-full rounded border-slate-300 text-sm focus:ring-indigo-500 font-bold text-blue-600 text-center shadow-sm px-1"
                                                placeholder="Max">
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] uppercase tracking-wider font-extrabold text-slate-500 mb-1">pH
                                            Level Std</label>
                                        <div class="flex items-center gap-1">
                                            <input type="number" step="0.1" x-model="phMin"
                                                class="w-full rounded border-slate-300 text-sm focus:ring-indigo-500 font-bold text-emerald-600 text-center shadow-sm px-1"
                                                placeholder="Min">
                                            <span class="text-slate-400 font-bold">-</span>
                                            <input type="number" step="0.1" x-model="phMax"
                                                class="w-full rounded border-slate-300 text-sm focus:ring-indigo-500 font-bold text-emerald-600 text-center shadow-sm px-1"
                                                placeholder="Max">
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] uppercase tracking-wider font-extrabold text-slate-500 mb-1">Temperatur
                                            Std <span class="text-orange-600">(°C)</span></label>
                                        <div class="flex items-center gap-1">
                                            <input type="number" step="1" x-model="tempMin"
                                                class="w-full rounded border-slate-300 text-sm focus:ring-indigo-500 font-bold text-orange-600 text-center shadow-sm px-1"
                                                placeholder="Min">
                                            <span class="text-slate-400 font-bold">-</span>
                                            <input type="number" step="1" x-model="tempMax"
                                                class="w-full rounded border-slate-300 text-sm focus:ring-indigo-500 font-bold text-orange-600 text-center shadow-sm px-1"
                                                placeholder="Max">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white px-4 py-4 sm:px-6 flex justify-end gap-3 border-t border-slate-200">
                            <button type="button" @click="isModalOpen = false"
                                class="bg-white border border-slate-300 text-slate-700 px-5 py-2 rounded-lg text-sm font-bold hover:bg-slate-50 transition">
                                Batal
                            </button>
                            <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg text-sm font-bold shadow-md transition">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Script Alpine.js Component --}}
    <script>
        function standardManager() {
            return {
                activeTab: 'Plant A',
                isModalOpen: false,
                editData: {},

                // Variabel Penampung Angka
                konsMin: '',
                konsMax: '',
                phMin: '',
                phMax: '',
                tempMin: '',
                tempMax: '',

                openModal(data) {
                    this.editData = data;

                    // 1. Memecah string "6% - 8%" menjadi angka murni 6 dan 8
                    let kons = (data.std_konsentrasi || '').replace(/%/g, '').split('-');
                    this.konsMin = kons[0] ? kons[0].trim() : '';
                    this.konsMax = kons[1] ? kons[1].trim() : '';

                    // 2. Memecah string "8 - 9" menjadi 8 dan 9
                    let ph = (data.std_ph || '').split('-');
                    this.phMin = ph[0] ? ph[0].trim() : '';
                    this.phMax = ph[1] ? ph[1].trim() : '';

                    // 3. Memecah string "35°C - 40°C" menjadi angka 35 dan 40
                    let temp = (data.std_temp || '').replace(/°C/g, '').replace(/C/g, '').split('-');
                    this.tempMin = temp[0] ? temp[0].trim() : '';
                    this.tempMax = temp[1] ? temp[1].trim() : '';

                    this.isModalOpen = true;
                },

                // PENGGABUNG OTOMATIS (Akan dikirim ke controller via input hidden)
                get formattedKonsentrasi() {
                    if (!this.konsMin && !this.konsMax) return '';
                    if (this.konsMin && !this.konsMax) return `${this.konsMin}%`;
                    return `${this.konsMin}% - ${this.konsMax}%`;
                },
                get formattedPh() {
                    if (!this.phMin && !this.phMax) return '';
                    if (this.phMin && !this.phMax) return `${this.phMin}`;
                    return `${this.phMin} - ${this.phMax}`;
                },
                get formattedTemp() {
                    if (!this.tempMin && !this.tempMax) return '';
                    if (this.tempMin && !this.tempMax) return `${this.tempMin}°C`;
                    return `${this.tempMin}°C - ${this.tempMax}°C`;
                }
            }
        }
    </script>
</x-app-layout>
