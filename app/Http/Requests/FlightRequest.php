<?php

namespace App\Http\Requests;

use App\Enums\SortBy;
use App\Enums\SortOrder;
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
            'departure_airport' => ['required', 'exists:airports,code'],
            'arrival_airport' => ['required', 'exists:airports,code'],
            'departure_date' => ['required','date_format:Y-m-d'],
            'type' => ['required', new Enum(TripType::class)],

            // Optional fields
            'return_date' => ['nullable',
                'required_if:type,round-trip',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:departure_date',
            ],
            'stops' => ['nullable','integer','min:0', 'max:1'],
            'airline' => ['nullable','exists:airlines,code'],

            'page_size' => ['nullable','integer','max:100'],
            'page' => ['nullable','integer'],
            'sort_by' => ['nullable', new Enum(SortBy::class)],
            'sort_order' => ['nullable', 'required_with:sort_by', new Enum(SortOrder::class)],
        ];
    }

    public function queryParameters()
    {
        return [
            'departure_airport' => [
                'description' => 'The departure airport IATA **code**',
                'example' => 'YUL'
            ],
            'arrival_airport' => [
                'description' => 'The arrival airport IATA **code**.',
                'example' => 'YVR'
            ],
            'departure_date' => [
                'description' => 'Date of departure.',
                'example' => '2022-02-01'
            ],
            'type' => [
                'description' => 'Trip type, it can be a **one-way** or **round-trip**.',
                'example' => 'round-trip'
            ],
            'return_date' => [
                'description' => 'Date of the return trip.',
                'example' => '2022-02-20'
            ],
            'stops' => [
                'description' => 'Number of stops, can be blank(all flights), 0(direct flights only) or 1. When 1, will filter flights with 1+ stops',
                'example' => 'No-example'
            ],
            'airline' => [
                'description' => 'IATA Code of the airline to filter the flights.',
                'example' => 'AC'
            ],
            'page_size' => [
                'description' => 'Size per page. Defaults to 10.',
                'example' => 'No-example'
            ],
            'page' => [
                'description' => 'Page to view.',
                'example' => 'No-example'
            ],
            'sort_by' => [
                'description' => 'Sorting field, currently can be only **price**.',
                'example' => 'price'
            ],
            'sort_order' => [
                'description' => 'Sorting order, can be either **asc** or **desc**.',
                'example' => 'asc'
            ],
        ];
    }
}
