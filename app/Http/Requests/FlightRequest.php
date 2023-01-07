<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlightRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // Airport code standards have maximum of 4 characters
            'departure_airport' => ['required', 'min:2', 'exists:airports,code'],
            'arrival_airport' => ['required', 'min:2', 'exists:airports,code'],

            // Optional fields
            'departure_time' => ['nullable','date_format:H:i'],
            'page_size' => ['nullable','integer','max:100'],
        ];
    }
}
