<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRatingRequest;
use App\Jobs\UpdateProblemRatingJob;
use App\Models\Problem;
use App\Models\Rating;
use App\Services\ContestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{

    public function store(StoreRatingRequest $request, Problem $problem, ContestService $contestService)
    {
        if ($contestService->inContest) {
            $problems = $contestService->contest->problems();
        } else {
            $problems = Problem::where(function ($query) {
                /** @var User */
                $user = Auth::user();
                if (!$user->isAdmin())
                    $query->where('user_id', $user->id)
                        ->orWhere('visible', true);
            });
        }
        if (!$problems->where('problems.id', $problem->id)->exists())
            return abort(404);
        UpdateProblemRatingJob::dispatch()->delay(now()->addSeconds(30))->afterCommit();
        return Rating::updateOrCreate([
            'problem_id' => $problem->id,
            'user_id' => Auth::user()->id,
        ], [
            'value' => $request->value,
            'computed' => false
        ]);
    }
}
