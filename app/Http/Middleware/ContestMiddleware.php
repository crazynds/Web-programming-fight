<?php

namespace App\Http\Middleware;

use App\Models\Competitor;
use App\Models\Contest;
use App\Services\ContestService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ContestMiddleware
{
    public function __construct(protected ContestService $contestService) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('contestService', $this->contestService);
        if (Auth::user() && Cache::has($key = 'contest:user:'.Auth::id())) {
            $contestData = Cache::get($key);
            $contest = Contest::find($contestData['contest'] ?? 0);
            $competitor = Competitor::find($contestData['competitor'] ?? 0);
            if (! $contest || ! $competitor || $contest->id != $competitor->contest_id) {
                Cache::forget($key);
            } else {
                // Check if the contest is already finished and throw away all competitors
                if ($contest->start_time->addMinutes($contest->duration)->lt(now())) {
                    Cache::forget($key);

                    return redirect()->route('contest.leaderboard', ['contest' => $contest->id]);
                } else {
                    $this->contestService->setContestCompetitor($contest, $competitor);
                }
            }
        }

        return $next($request);
    }
}
