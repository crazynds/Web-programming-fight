<?php

namespace App\Http\Requests;

use App\Models\BrasileiraoWeeklyChallenge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreBrasileiraoWeeklyChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewPulse') ?? false;
    }

    public function rules(): array
    {
        return [
            'problem_id'   => 'required|exists:problems,id',
            'league'       => 'required|in:bixos,veteranos,all',
            'ranking_type' => 'required|in:cpu_time,memory_peak,submission_time,code_lines,test_cases',
            'start_at'     => 'required|date',
            'end_at'       => 'required|date|after:start_at',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function () {
            /** @var BrasileiraoWeeklyChallenge|null $challenge */
            $challenge = $this->route('challenge');
            $excludeId = $challenge?->id;

            $league  = $this->input('league');
            $startAt = $this->input('start_at');
            $endAt   = $this->input('end_at');

            $query = BrasileiraoWeeklyChallenge::where(function ($q) use ($league) {
                $q->where('league', $league)->orWhere('league', 'all');
                if ($league === 'all') {
                    $q->orWhere('league', 'bixos')->orWhere('league', 'veteranos');
                }
            })->where(function ($q) use ($startAt, $endAt) {
                $q->where('start_at', '<', $endAt)->where('end_at', '>', $startAt);
            });

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if ($query->exists()) {
                throw ValidationException::withMessages([
                    'start_at' => 'There is already a challenge active during this period for this league.',
                ]);
            }
        });
    }
}
