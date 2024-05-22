<?php

namespace App\Http\Requests;

use App\Enums\LanguagesType;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class StoreContestRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|min:5',
            'description' => 'required|string|max:65000',
            'is_private' => 'required|boolean',
            'password' => 'sometimes|nullable|string|min:8|max:255',
            'start_time' => 'required|date_format:Y-m-d\TH:i|after:now',
            'duration' => 'required|integer|min:0|max:43200',
            'blind_time' => 'required|integer|min:0|max:20160',
            'penality' => 'required|integer|min:0|max:60000',

            // Rules
            'parcial_solution' => 'required|boolean',
            'show_wrong_answer' => 'required|boolean',

            'problems' => 'required|array|min:1',
            'problems.*' => 'required|integer|exists:problems,id',

            'languages' => 'required|array|min:1',
            'languages.*' => [
                'required',
                'integer',
                new EnumValue(LanguagesType::class, false)
            ],
        ];
    }
}
