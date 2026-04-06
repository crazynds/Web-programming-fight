<?php

namespace App\Http\Requests;

use App\Enums\LanguagesType;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
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
        $hasFile = $this->hasFile('code_file');

        return [
            'problem' => 'required|integer|exists:problems,id',
            'lang' => [
                'required',
                new EnumValue(LanguagesType::class, false),
            ],
            'code_file' => $hasFile
                ? ['nullable', 'file', ...LanguagesType::validation($this->input('lang', 0))]
                : ['nullable'],
            'code_text' => $hasFile
                ? ['nullable', 'string']
                : ['required', 'string', 'min:1', 'max:' . (1024 * 1024)],
            'g-recaptcha-response' => 'required|recaptchav3:submit,0.5',
        ];
    }
}
