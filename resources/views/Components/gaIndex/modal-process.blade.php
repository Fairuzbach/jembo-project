{{-- Modal Process GA --}}
<div id="modal-process-ga" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
            onclick="closeProcessModal()"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="form-process-ga" action="" method="POST">
                @csrf
                @method('PUT')

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                </path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title-ga">Proses Tiket
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 mb-4">
                                    Tentukan status untuk tiket <span id="modal-ticket-num"
                                        class="font-bold text-gray-800"></span>
                                    dari <span id="modal-requester-name" class="font-bold text-gray-800"></span>.
                                </p>

                                <div class="mb-4 bg-gray-50 p-3 rounded-md border border-gray-200">
                                    <label
                                        class="block text-gray-700 text-xs font-bold mb-2 uppercase tracking-wide">Tindakan:</label>
                                    <div class="flex items-center space-x-6">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" name="action" value="approve" checked
                                                onclick="toggleReasonGa(false)"
                                                class="form-radio text-green-600 h-4 w-4 focus:ring-green-500">
                                            <span class="ml-2 text-sm font-medium text-gray-700">Approve
                                                (Setujui)</span>
                                        </label>
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" name="action" value="reject"
                                                onclick="toggleReasonGa(true)"
                                                class="form-radio text-red-600 h-4 w-4 focus:ring-red-500">
                                            <span class="ml-2 text-sm font-medium text-gray-700">Reject (Tolak)</span>
                                        </label>
                                    </div>
                                </div>

                                <div id="reason-container-ga" class="hidden transition-all duration-300">
                                    <label for="reason" class="block text-gray-700 text-sm font-bold mb-1">
                                        Alasan Penolakan <span class="text-red-500">*</span>
                                    </label>
                                    <textarea name="reason" id="reason-input-ga" rows="3"
                                        class="shadow-sm focus:ring-red-500 focus:border-red-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md p-2"
                                        placeholder="Jelaskan alasan penolakan secara singkat..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit"
                        onclick="confirmSubmit(event, 'Simpan Keputusan?', 'Pastikan data sudah benar.', 'question')"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Simpan Keputusan
                    </button>
                    <button type="button" onclick="closeProcessModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
