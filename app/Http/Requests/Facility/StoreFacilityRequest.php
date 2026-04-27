<?php

namespace App\Http\Requests\Facility;

use Illuminate\Foundation\Http\FormRequest;

/**
 * =========================================================================
 * StoreFacilityRequest
 * =========================================================================
 * Form Request untuk validasi pembuatan Work Order Facility baru.
 * Dipanggil dari FacilitiesController@store.
 *
 * File yang diizinkan untuk diupload:
 * - Gambar : jpg, jpeg, png, webp
 * - Dokumen: pdf, xlsx, xls
 * - Maksimal ukuran file: 10MB (10240 KB)
 * =========================================================================
 */
class StoreFacilityRequest extends FormRequest
{
    /**
     * Semua user yang sudah login diizinkan membuat tiket.
     * Pembatasan akses lebih lanjut dilakukan di Controller & Middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk setiap field form.
     *
     * Catatan khusus field machine_id:
     * - Wajib diisi jika kategori termasuk dalam $categoriesReqMachine
     * - Menggunakan custom closure validation karena kondisi required
     *   bergantung pada nilai field lain (category), bukan sekedar required_if
     *
     * Catatan khusus field new_machine_name:
     * - Hanya wajib diisi jika kategori = 'Pemasangan Mesin'
     * - Digunakan untuk mendaftarkan mesin baru ke database
     */
    public function rules(): array
    {
        return [
            // Nama requester — diisi otomatis dari Auth::user()->name di Service
            'requester_name' => 'required|string|max:255',

            // Plant harus ada di tabel plants
            'plant_id'       => 'required|exists:plants,id',

            // Kategori pekerjaan (dropdown)
            'category'       => 'required|string',

            // Deskripsi masalah / kendala
            'description'    => 'required|string',

            // File attachment — opsional
            // Allowed: jpg, jpeg, png, webp, pdf, xlsx, xls | Max: 10MB
            'photo'          => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,xlsx,xls|max:10240',

            // Nama mesin baru — hanya wajib untuk kategori Pemasangan Mesin
            'new_machine_name' => 'required_if:category,Pemasangan Mesin|nullable|string|max:255',

            // Mesin yang dipilih — wajib untuk kategori tertentu (custom validation)
            'machine_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    /**
                     * Kategori yang wajib memilih mesin dari dropdown.
                     * Jika kategori ini dipilih tapi machine_id kosong → validasi gagal.
                     */
                    $categoriesReqMachine = [
                        'Modifikasi Mesin',
                        'Pembongkaran Mesin',
                        'Relokasi Mesin',
                        'Perbaikan',
                        'Pembuatan Alat Baru',
                    ];

                    if (in_array($this->category, $categoriesReqMachine) && empty($value)) {
                        $fail('Wajib memilih mesin untuk kategori ini.');
                    }
                },
            ],
        ];
    }

    /**
     * Pesan error kustom untuk validasi.
     */
    public function messages(): array
    {
        return [
            'requester_name.required' => 'Nama requester wajib diisi.',
            'plant_id.required'       => 'Plant wajib dipilih.',
            'plant_id.exists'         => 'Plant yang dipilih tidak valid.',
            'category.required'       => 'Kategori wajib dipilih.',
            'description.required'    => 'Deskripsi masalah wajib diisi.',
            'photo.mimes'             => 'File hanya boleh berupa gambar (jpg, png, webp), PDF, atau Excel (xlsx, xls).',
            'photo.max'               => 'Ukuran file maksimal 10MB.',
            'new_machine_name.required_if' => 'Nama mesin baru wajib diisi untuk kategori Pemasangan Mesin.',
        ];
    }
}
