<?php

namespace App\Http\Requests\GA;

use Illuminate\Foundation\Http\FormRequest;

class ProcessTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject'],
            'reason' => ['required_if:action,reject', 'nullable', 'string', 'min:5'],
        ];
    }
}
