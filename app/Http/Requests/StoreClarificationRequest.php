<?php

namespace App\Http\Requests;

use App\Services\ContestService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClarificationRequest extends FormRequest
{

    public function __construct(protected ContestService $contestService)
    {
    }

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
            'question' => 'required|string|max:4000|min:16',
            'problem_id' => [
                'required',
                Rule::exists('contest_problem', 'problem_id')->where('contest_id', $this->contestService->contest->id)
            ]
        ];
    }
}
