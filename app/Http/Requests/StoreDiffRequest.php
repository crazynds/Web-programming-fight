<?php

namespace App\Http\Requests;

use App\Enums\LanguagesType;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiffRequest extends FormRequest
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
            ],
            recaptchaFieldName() => recaptchaRuleName()
        ];
    }
}
