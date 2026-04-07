<?php

namespace App\Http\Requests;

use App\Enums\TagTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'alias' => 'required|string|max:255',
            'type' => [
                'required',
                Rule::enum(TagTypeEnum::class),
            ],

            'problems' => 'required|array|min:1',
            'problems.*' => 'required|integer|exists:problems,id',
            'g-recaptcha-response' => 'required|recaptchav3:submit,0.4',
        ];
    }
}
