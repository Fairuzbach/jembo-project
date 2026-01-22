<?php

namespace App\Http\Requests\Facility;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacilityRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'requester_name' => 'required|string|max:255',
            'plant_id' => 'required|exists:plants,id',
            'category' => 'required|string',
            'description' => 'required|string',
            'photo' => 'nullable|image|max:5120',

            'new_machine_name' => 'required_if:category, Pemasangan Mesin|nullable|string|max:255',
            'machine_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $categoriesReqMachine = [
                        'Modifikasi Mesin',
                        'Pembongkaran Mesin',
                        'Relokasi Mesin',
                        'Perbaikan',
                        'Pembuatan Alat Baru'
                    ];

                    if (in_array($this->category, $categoriesReqMachine) && empty($value)) {
                        $fail('Wajib memilih mesin untuk kategori ini.');
                    }
                },
            ],
        ];
    }
}
