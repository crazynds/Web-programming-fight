<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProblemRequest extends FormRequest
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
            'title' => 'required|string|min:3|max:255',
            'author' => 'string|min:1|max:255',
            'time_limit' => 'required|integer|min:50|max:120000',
            'memory_limit' => 'required|integer|min:10|max:8192',
            'description' => 'required|string|max:65000',
            'input_description' => 'required|string|max:32000',
            'output_description' => 'required|string|max:32000',
            recaptchaFieldName() => recaptchaRuleName()
        ];
    }
}
