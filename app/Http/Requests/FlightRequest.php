<?php

namespace App\Http\Requests;

use App\Enums\TripType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
            'departure_date' => ['required','date_format:Y-m-d'],
            'type' => ['required', new Enum(TripType::class)],

            // Optional fields
            'return_date' => ['nullable',
                'required_if:type,round-trip',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:departure_date',
            ],
            'stops' => ['nullable','integer','min:0', 'max:2'],
            'airline' => ['nullable','exists:airline,code'],

            'page_size' => ['nullable','integer','max:100'],
            'page' => ['nullable','integer'],
        ];
    }
}
