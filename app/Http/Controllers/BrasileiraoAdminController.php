<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrasileiraoWeeklyChallengeRequest;
use App\Jobs\ScoreBrasileiraoChallengeJob;
use App\Models\BrasileiraoWeeklyChallenge;
use App\Models\Problem;

class BrasileiraoAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:viewPulse');
    }

    public function index()
    {
        $challenges = BrasileiraoWeeklyChallenge::with('problem')
            ->orderByDesc('start_at')
            ->get();

        return view('pages.competition.admin.index', compact('challenges'));
    }

    public function create()
    {
        $problems = Problem::orderBy('title')->get();

        return view('pages.competition.admin.form', compact('problems'));
    }

    public function store(StoreBrasileiraoWeeklyChallengeRequest $request)
    {
        $data = $request->validated();

        $challenge = BrasileiraoWeeklyChallenge::create($data);

        ScoreBrasileiraoChallengeJob::dispatch($challenge)->delay($challenge->end_at);

        return redirect()->route('competition.admin.index')
            ->with('success', 'Weekly challenge created successfully.');
    }

    public function edit(BrasileiraoWeeklyChallenge $challenge)
    {
        $problems = Problem::orderBy('title')->get();

        return view('pages.competition.admin.form', compact('challenge', 'problems'));
    }

    public function update(StoreBrasileiraoWeeklyChallengeRequest $request, BrasileiraoWeeklyChallenge $challenge)
    {
        $data = $request->validated();

        $challenge->update($data);

        // Re-dispatch in case end_at changed; the job checks scored_at and end_at before scoring
        ScoreBrasileiraoChallengeJob::dispatch($challenge)->delay($challenge->end_at);

        return redirect()->route('competition.admin.index')
            ->with('success', 'Weekly challenge updated successfully.');
    }

    public function destroy(BrasileiraoWeeklyChallenge $challenge)
    {
        $challenge->delete();

        return redirect()->route('competition.admin.index')
            ->with('success', 'Weekly challenge deleted.');
    }

}
