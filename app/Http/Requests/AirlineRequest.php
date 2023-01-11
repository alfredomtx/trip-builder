<?php

namespace App\Http\Requests;

use App\Models\Airline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AirlineRequest extends FormRequest
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
        // When updating, all fields are optional, and when inserting, required.
        $isUpdateOperation = ($this->id ?? false);
        $requiredFields = ($isUpdateOperation) ? 'nullable' : 'required';
        return [
            'name' => [$requiredFields],
            'code' => [$requiredFields, Rule::unique(Airline::class)->ignore($this->id ?? null)],
        ];
    }

    public function queryParameters()
    {
        return [
            'name' => [
                'description' => 'Name of the airline to filter the flights.',
                'example' => 'Air Canada'
            ],
            'code' => [
                'description' => 'IATA Code of the airline to filter the flights.',
                'example' => 'AC'
            ],
        ];
    }
}
