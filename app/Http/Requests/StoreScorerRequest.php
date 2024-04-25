<?php

namespace App\Http\Requests;

use App\Enums\LanguagesType;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScorerRequest extends FormRequest
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
            'time_limit' => 'required|integer|min:50|max:120000',
            'memory_limit' => 'required|integer|min:10|max:8192',
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'lang' => [
                'required',
                new EnumValue(LanguagesType::class, false)
            ],
            'code' => [
                'required',
                ...LanguagesType::validation($this->input('lang', 0)),
            ],
            'input' => [
                'required',
                'file',
                // 100 MB
                'max:102400',
            ]
        ];
    }
}
