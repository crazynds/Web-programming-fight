<?php

namespace App\Http\Requests;

use App\Enums\LanguagesType;
use Illuminate\Foundation\Http\FormRequest;
use BenSampo\Enum\Rules\EnumValue;

class StoreSubmitRunRequest extends FormRequest
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
            'lang' => [
                'required',
                new EnumValue(LanguagesType::class,false)
            ],
            'code' => [
                'required',
                ...LanguagesType::validation($this->input('lang',0)),
            ]
        ];
    }
}
