<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRatingRequest;
use App\Models\Problem;
use App\Models\Rating;
use App\Services\ContestService;

class RatingController extends Controller
{
    public function store(StoreRatingRequest $request, Problem $problem, ContestService $contestService)
    {
        if ($contestService->inContest) {
            $problems = $contestService->contest->problems();
        } else {
            $problems = Problem::where(function ($query) {
                /** @var User */
                $user = $this->user();
                if (! $user->isAdmin()) {
                    $query->where('user_id', $user->id)
                        ->orWhere('visible', true);
                }
            });
        }
        if (! $problems->where('problems.id', $problem->id)->exists()) {
            return abort(404);
        }

        return Rating::updateOrCreate([
            'problem_id' => $problem->id,
            'user_id' => $this->user()->id,
        ], [
            'value' => $request->value,
            'computed' => false,
        ]);
    }
}
