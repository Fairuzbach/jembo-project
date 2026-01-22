<?php

namespace App\Http\Requests\GA;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // app/Http/Requests/GA/UpdateStatusRequest.php

    public function rules(): array
    {
        $rules = [
            'status'            => ['required', 'string'],
            'processed_by_name' => ['required', 'string'],
            'category'          => ['required', 'string'],
            'start_date'        => ['nullable', 'date'],
            'target_date'       => ['nullable', 'date'],
            'department'        => ['nullable', 'string'],
            'completion_photo'  => ['nullable', 'image', 'max:5120'],

            // --- TAMBAHKAN BARIS INI ---
            'actual_completion_date' => ['nullable', 'date'],
            'completion_note'        => ['nullable', 'string'],
        ];

        if ($this->status === 'cancelled') {
            $rules['cancellation_note'] = ['required', 'string', 'min:5'];
        }

        return $rules;
    }
}
