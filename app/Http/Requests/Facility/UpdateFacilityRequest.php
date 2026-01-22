<?php

namespace App\Http\Requests\Facility;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFacilityRequest extends FormRequest
{

    public function authorize()
    {
        return in_array($this->user()->role, ['fh.admin']);
    }

    public function rules()
    {
        return [
            'status' => 'required|in:pending, in_progress, completed, cancelled',
            'facility_tech_id' => 'nullable',
            'start_date' => 'nullable|date'
        ];
    }
}
