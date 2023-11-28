<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestCaseRequest extends FormRequest
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
            'input' =>[
                'array'
            ],
            'output' =>[
                'array'
            ],
            'input[]' => [
                'file',
                // 50 MB
                'size:51200',
            ],
            'output[]' => [
                'file',
                // 50 MB
                'size:51200',
            ]
        ];
    }
}
