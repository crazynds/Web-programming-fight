<?php

namespace App\Http\Controllers;

use App\Jobs\ScoreBrasileiraoChallengeJob;
use App\Models\BrasileiraoWeeklyChallenge;
use App\Models\Problem;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $data = $request->validate([
            'problem_id' => 'required|exists:problems,id',
            'league' => 'required|in:bixos,veteranos,all',
            'ranking_type' => 'required|in:cpu_time,memory_peak,submission_time,code_lines',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
        ]);

        $this->checkNoOverlap($data['league'], $data['start_at'], $data['end_at']);

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

    public function update(Request $request, BrasileiraoWeeklyChallenge $challenge)
    {
        $data = $request->validate([
            'problem_id' => 'required|exists:problems,id',
            'league' => 'required|in:bixos,veteranos,all',
            'ranking_type' => 'required|in:cpu_time,memory_peak,submission_time,code_lines',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
        ]);

        $this->checkNoOverlap($data['league'], $data['start_at'], $data['end_at'], $challenge->id);

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

    /**
     * Ensure no other challenge overlaps in time for the same league.
     * A league='all' challenge blocks bixos and veteranos, and vice-versa.
     */
    private function checkNoOverlap(string $league, string $startAt, string $endAt, ?int $excludeId = null): void
    {
        $query = BrasileiraoWeeklyChallenge::where(function ($q) use ($league) {
            $q->where('league', $league)
                ->orWhere('league', 'all');
            if ($league !== 'all') {
                $q->orWhere('league', $league);
            } else {
                // 'all' conflicts with everything
                $q->orWhere('league', 'bixos')->orWhere('league', 'veteranos');
            }
        })
            ->where(function ($q) use ($startAt, $endAt) {
                // Overlaps if not (ends before new start OR starts after new end)
                $q->where('start_at', '<', $endAt)
                    ->where('end_at', '>', $startAt);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'start_at' => 'There is already a challenge active during this period for this league.',
            ]);
        }
    }
}
