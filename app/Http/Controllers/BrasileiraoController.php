<?php

namespace App\Http\Controllers;

use App\Enums\SubmitResult;
use App\Models\BrasileiraoInstitution;
use App\Models\BrasileiraoMember;
use App\Models\BrasileiraoWeeklyChallenge;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrasileiraoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $member = BrasileiraoMember::where('user_id', Auth::id())->with('institution')->first();
        $league = $member ? ($member->role === 'bixo' ? 'bixos' : 'veteranos') : 'bixos';

        // If there is an active challenge for this league, redirect to it
        $activeChallenge = BrasileiraoWeeklyChallenge::active()->forLeague($league)->first();
        if ($activeChallenge) {
            return redirect()->route('competition.challenge', $activeChallenge);
        }

        return redirect()->route($league === 'bixos' ? 'competition.liga.bixos' : 'competition.liga.veteranos');
    }

    public function showChallenge(BrasileiraoWeeklyChallenge $challenge)
    {
        $challenge->load('problem');

        // Auto-enable problem visibility when the challenge goes live
        if ($challenge->isActive() && ! $challenge->problem->visible) {
            $challenge->problem->update(['visible' => true]);
        }

        $member = BrasileiraoMember::where('user_id', Auth::id())->with('institution')->first();

        $metricColumn = match ($challenge->ranking_type) {
            'cpu_time' => 'execution_time',
            'memory_peak' => 'execution_memory',
            'submission_time' => 'created_at',
            'code_lines' => 'code_lines',
        };

        // Subquery: best metric value per user within the challenge window
        $bestPerUser = Submission::where('problem_id', $challenge->problem_id)
            ->where('result', SubmitResult::Accepted)
            ->whereBetween('created_at', [$challenge->start_at, $challenge->end_at])
            ->selectRaw("user_id, MIN({$metricColumn}) as best_value")
            ->groupBy('user_id');

        // Join back to get the full submission row for that best value (one row per user)
        $leaderboard = Submission::where('submissions.problem_id', $challenge->problem_id)
            ->where('submissions.result', SubmitResult::Accepted)
            ->whereBetween('submissions.created_at', [$challenge->start_at, $challenge->end_at])
            ->joinSub($bestPerUser, 'best', function ($join) use ($metricColumn) {
                $join->on('submissions.user_id', '=', 'best.user_id')
                    ->whereColumn("submissions.{$metricColumn}", 'best.best_value');
            })
            ->orderBy('best.best_value')
            ->orderBy('submissions.id') // tiebreak: earlier submission wins
            ->select('submissions.*')
            ->with('user')
            ->get()
            ->unique('user_id') // guard against duplicate rows on exact ties
            ->values();

        // Compute which submissions qualify for the bonus point (1st place + 5% technical tie)
        $bonusSubmissionIds = collect();
        if ($leaderboard->isNotEmpty()) {
            $first = $leaderboard->first();
            $firstValue = $challenge->ranking_type === 'submission_time'
                ? $first->created_at->diffInSeconds($challenge->start_at)
                : $first->{$metricColumn};

            foreach ($leaderboard as $submission) {
                $value = $challenge->ranking_type === 'submission_time'
                    ? $submission->created_at->diffInSeconds($challenge->start_at)
                    : $submission->{$metricColumn};

                if ($firstValue == 0 || abs($value - $firstValue) / $firstValue <= 0.05) {
                    $bonusSubmissionIds->push($submission->id);
                }
            }
        }

        return view('pages.competition.challenge', [
            'challenge' => $challenge,
            'leaderboard' => $leaderboard,
            'member' => $member,
            'bonusSubmissionIds' => $bonusSubmissionIds,
        ]);
    }

    public function ligaBixos()
    {
        $institutions = BrasileiraoInstitution::withCount(['members', 'bixos', 'veterans', 'coaches'])
            ->having('bixos_count', '>', 0)
            ->orderByDesc('points')
            ->orderByDesc('bixos_count')
            ->get();

        $member = BrasileiraoMember::where('user_id', Auth::id())->with('institution')->first();

        $challenges = BrasileiraoWeeklyChallenge::with('problem')
            ->forLeague('bixos')
            ->orderByDesc('start_at')
            ->get();

        return view('pages.competition.brasileirao', [
            'institutions' => $institutions,
            'member' => $member,
            'league' => 'bixos',
            'challenges' => $challenges,
        ]);
    }

    public function ligaVeteranos()
    {
        $institutions = BrasileiraoInstitution::withCount(['members', 'bixos', 'veterans', 'coaches'])
            ->having('veterans_count', '>', 0)
            ->orderByDesc('points')
            ->orderByDesc('veterans_count')
            ->get();

        $member = BrasileiraoMember::where('user_id', Auth::id())->with('institution')->first();

        $challenges = BrasileiraoWeeklyChallenge::with('problem')
            ->forLeague('veteranos')
            ->orderByDesc('start_at')
            ->get();

        return view('pages.competition.brasileirao', [
            'institutions' => $institutions,
            'member' => $member,
            'league' => 'veteranos',
            'challenges' => $challenges,
        ]);
    }

    public function showInstitution(BrasileiraoInstitution $institution)
    {
        $members = $institution->members()->with('user')->get();

        $bixosStats = $this->computeInstitutionStats($members, ['bixo'], ['bixos', 'all']);
        $veteranosStats = $this->computeInstitutionStats($members, ['veteran'], ['veteranos', 'all']);

        return view('pages.competition.institution', compact('institution', 'bixosStats', 'veteranosStats'));
    }

    private function computeInstitutionStats($members, array $roles, array $challengeLeagues)
    {
        $eligible = $members->whereIn('role', $roles);
        $eligibleUserIds = $eligible->pluck('user_id');

        $stats = [];
        foreach ($eligibleUserIds as $uid) {
            $stats[$uid] = ['solved' => 0, 'bonus' => 0, 'attempts' => 0];
        }

        if ($eligibleUserIds->isEmpty()) {
            return collect();
        }

        $challenges = BrasileiraoWeeklyChallenge::whereIn('league', $challengeLeagues)
            ->where('start_at', '<=', now())
            ->get();

        foreach ($challenges as $challenge) {
            $eligibleRoles = match ($challenge->league) {
                'bixos' => ['bixo'],
                'veteranos' => ['veteran'],
                default => ['bixo', 'veteran'],
            };
            // Only members matching both this league's eligible roles and the requested roles
            $challengeUserIds = $eligible->whereIn('role', $eligibleRoles)->pluck('user_id');

            if ($challengeUserIds->isEmpty()) {
                continue;
            }

            $metricColumn = match ($challenge->ranking_type) {
                'cpu_time' => 'execution_time',
                'memory_peak' => 'execution_memory',
                'submission_time' => 'created_at',
                'code_lines' => 'code_lines',
            };

            $windowSubmissions = Submission::whereBetween('created_at', [$challenge->start_at, $challenge->end_at])
                ->where('problem_id', $challenge->problem_id)
                ->whereIn('user_id', $challengeUserIds)
                ->get();

            foreach ($windowSubmissions->groupBy('user_id') as $uid => $subs) {
                if (isset($stats[$uid])) {
                    $stats[$uid]['attempts'] += $subs->count();
                }
            }

            $accepted = $windowSubmissions->where('result', SubmitResult::Accepted);
            $bestPerUser = $accepted->groupBy('user_id')->map(function ($subs) use ($metricColumn, $challenge) {
                return $subs->sortBy(function ($s) use ($metricColumn, $challenge) {
                    return $challenge->ranking_type === 'submission_time'
                        ? $s->created_at->diffInSeconds($challenge->start_at)
                        : $s->{$metricColumn};
                })->first();
            });

            foreach ($bestPerUser as $uid => $submission) {
                if (isset($stats[$uid])) {
                    $stats[$uid]['solved']++;
                }
            }

            if ($bestPerUser->isNotEmpty()) {
                $allEligibleUserIds = BrasileiraoMember::whereIn('role', $eligibleRoles)->pluck('user_id');
                $globalBest = Submission::where('problem_id', $challenge->problem_id)
                    ->where('result', SubmitResult::Accepted)
                    ->whereBetween('created_at', [$challenge->start_at, $challenge->end_at])
                    ->whereIn('user_id', $allEligibleUserIds)
                    ->selectRaw("user_id, MIN({$metricColumn}) as best_value")
                    ->groupBy('user_id')
                    ->orderBy('best_value')
                    ->first();

                if ($globalBest) {
                    $firstValue = $challenge->ranking_type === 'submission_time'
                        ? \Carbon\Carbon::parse($globalBest->best_value)->diffInSeconds($challenge->start_at)
                        : $globalBest->best_value;

                    foreach ($bestPerUser as $uid => $submission) {
                        $value = $challenge->ranking_type === 'submission_time'
                            ? $submission->created_at->diffInSeconds($challenge->start_at)
                            : $submission->{$metricColumn};

                        if ($firstValue == 0 || abs($value - $firstValue) / $firstValue <= 0.05) {
                            if (isset($stats[$uid])) {
                                $stats[$uid]['bonus']++;
                            }
                        }
                    }
                }
            }
        }

        return $eligible->map(function ($member) use ($stats) {
            $s = $stats[$member->user_id] ?? ['solved' => 0, 'bonus' => 0, 'attempts' => 0];

            return (object) [
                'member' => $member,
                'solved' => $s['solved'],
                'bonus' => $s['bonus'],
                'attempts' => $s['attempts'],
                'score' => $s['solved'] + $s['bonus'],
            ];
        })->sort(function ($a, $b) {
            if ($b->score !== $a->score) {
                return $b->score - $a->score;
            }

            return $a->attempts - $b->attempts;
        })->values();
    }

    public function showRegister()
    {
        if (BrasileiraoMember::where('user_id', Auth::id())->exists()) {
            return redirect()->route('competition.brasileirao')
                ->with('error', 'You are already registered for the Brasileirao.');
        }

        return view('pages.competition.register');
    }

    public function howItWorks()
    {
        return view('pages.competition.how-it-works');
    }

    public function register(Request $request)
    {
        $request->validate([
            'institution_acronym' => 'required|string|max:10',
            'role' => 'required|in:coach,bixo,veteran',
        ]);

        $user = Auth::user();

        if (BrasileiraoMember::where('user_id', $user->id)->exists()) {
            return redirect()->route('competition.brasileirao')
                ->with('error', 'You are already registered for the Brasileirao.');
        }

        $acronym = strtoupper(trim($request->institution_acronym));

        $institution = BrasileiraoInstitution::firstOrCreate(
            ['acronym' => $acronym],
            ['points' => 0]
        );

        BrasileiraoMember::create([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'role' => $request->role,
        ]);

        return redirect()->route('competition.brasileirao')
            ->with('success', 'Successfully registered for the Brasileirao de Programacao!');
    }
}
