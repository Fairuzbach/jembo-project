<?php

namespace App\Http\Requests\GA;

use Illuminate\Foundation\Http\FormRequest;
// use Illuminate\Validation\Rules\File; // Opsional jika pakai syntax baru

class StoreWorkOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // 1. Validasi NIK (Cek ke tabel users)
            'requester_nik' => ['required', 'string', 'exists:users,nik'],

            // 2. Validasi Plant ID (Cek ke tabel plants)
            'plant_id'      => ['required', 'integer', 'exists:plants,id'],

            'department'    => ['required', 'string'],
            'description'   => ['required', 'string'],
            'category'      => ['required', 'string'],
            'parameter_permintaan' => ['required', 'string'],

            // 3. Validasi Tanggal (Tidak boleh masa lalu)
            'target_completion_date' => ['nullable', 'date', 'after_or_equal:today'],

            // 4. VALIDASI FILE ANTI .EXE (PENTING!)
            // Pastikan nama field di form Anda adalah 'photo' (sesuai controller sebelumnya)
            // Jika di form namanya 'file', ganti 'photo' jadi 'file' di bawah ini.
            'photo' => [
                'nullable',           // Boleh kosong
                'file',               // Harus berupa file upload
                'image',              // Validasi dasar image
                'mimes:jpg,jpeg,png,webp', // SECURITY: Cek isi biner file (MIME Types)
                'max:5120',           // Max 5MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.image' => 'File yang diupload harus berupa gambar.',
            'photo.mimes' => 'Format file tidak didukung. Harap upload: JPG, JPEG, PNG, WEBP',
            'photo.max' => 'Ukuran file terlalu besar. Maksimal 5mb'
        ];
    }
}
