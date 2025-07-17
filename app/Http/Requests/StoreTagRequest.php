<?php

namespace App\Http\Requests;

use App\Enums\TagTypeEnum;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

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
                new EnumValue(TagTypeEnum::class, false),
            ],

            'problems' => 'required|array|min:1|max:32',
            'problems.*' => 'required|integer|exists:problems,id',
            recaptchaFieldName() => recaptchaRuleName(),
        ];
    }
}
