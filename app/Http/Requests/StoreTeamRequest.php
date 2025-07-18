<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $data = $this->all();
        if (isset($data['acronym'])) {
            $data['acronym'] = Str::upper($data['acronym']);
        }
        $this->replace($data);

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $teamId = $this->route('team')?->id;
        if (! $teamId) {
            $teamId = -1;
        }

        return [
            'name' => 'required|string|min:2|max:40',
            'acronym' => 'required|string|min:3|max:5|alpha_dash:ascii|unique:teams,acronym,'.$teamId,
            'membersjson' => 'nullable|json',
            'institution_acronym' => 'required|string|max:10',
            'country' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            recaptchaFieldName() => recaptchaRuleName(),
        ];
    }
}
