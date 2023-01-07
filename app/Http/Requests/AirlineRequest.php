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
            'name' => [$requiredFields, 'min:2'],
            'code' => [$requiredFields, 'min:2', Rule::unique(Airline::class)->ignore($this->id ?? null)],
        ];
    }
}
