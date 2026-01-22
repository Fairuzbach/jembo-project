            {{-- MODAL ACCEPT GA --}}
            <template x-teleport="body">
                <div x-show="showAcceptModal" style="display: none;" class="fixed inset-0 z-[60] overflow-y-auto">
                    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="showAcceptModal = false"></div>
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="relative w-full max-w-md bg-white rounded-xl shadow-2xl p-6">
                            <h3 class="text-lg font-black text-slate-800 uppercase mb-2">Terima Tiket</h3>
                            <p class="text-sm text-slate-500 mb-6">Siapa PIC yang akan mengerjakan tiket ini?</p>
                            <form :action="'/ga/approve/' + acceptId" method="POST">
                                @csrf
                                <div class="mb-6">
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Nama PIC /
                                        Teknisi</label>
                                    <input type="text" name="processed_by_name" required
                                        class="w-full border-2 border-slate-300 rounded-lg text-sm font-bold h-11 px-3">
                                </div>
                                <div class="flex justify-end gap-3">
                                    <button type="button" @click="showAcceptModal = false"
                                        class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-lg uppercase text-xs">Batal</button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-emerald-500 text-white font-bold rounded-lg uppercase text-xs">Simpan
                                        & Terima</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </template>
