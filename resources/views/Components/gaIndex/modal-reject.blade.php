          <template x-teleport="body">
              <div x-show="showRejectModal" style="display: none;" class="fixed inset-0 z-[70] overflow-y-auto">
                  <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="showRejectModal = false"></div>
                  <div class="flex min-h-full items-center justify-center p-4">
                      <div class="relative w-full max-w-md bg-white rounded-xl shadow-2xl border-t-4 border-red-500 p-6">
                          <h3 class="text-lg font-black text-slate-800 uppercase mb-4">Tolak Tiket</h3>
                          <form :action="'/ga/reject/' + rejectId" method="POST">
                              @csrf
                              <div class="mb-6">
                                  <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Alasan
                                      Penolakan <span class="text-red-500">*</span></label>
                                  <textarea name="reason" rows="3" required class="w-full border-2 border-slate-300 rounded-lg text-sm p-3"></textarea>
                              </div>
                              <div class="flex justify-end gap-3">
                                  <button type="button" @click="showRejectModal = false"
                                      class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-lg uppercase text-xs">Batal</button>
                                  <button type="submit"
                                      class="px-4 py-2 bg-red-500 text-white font-bold rounded-lg uppercase text-xs">Tolak
                                      Tiket</button>
                              </div>
                          </form>
                      </div>
                  </div>
              </div>
          </template>
