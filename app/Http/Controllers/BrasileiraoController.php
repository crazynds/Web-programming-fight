<?php

namespace App\Http\Controllers;

use App\Enums\SubmitResult;
use App\Models\BrasileiraoContest;
use App\Models\BrasileiraoInstitution;
use App\Models\BrasileiraoMember;
use App\Models\BrasileiraoWeeklyChallenge;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BrasileiraoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only(['register']);
    }

    public function index()
    {
        $member = Auth::check()
            ? BrasileiraoMember::where('user_id', Auth::id())->with('institution')->first()
            : null;

        // Unauthenticated or unregistered users go straight to leaderboard
        if (! $member) {
            return redirect()->route('competition.liga.bixos');
        }

        $league = $member->role === 'bixo' ? 'bixos' : 'veteranos';

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

        $member = Auth::check()
            ? BrasileiraoMember::where('user_id', Auth::id())->with('institution')->first()
            : null;

        // Determine which roles are eligible for this league
        $eligibleRoles = match ($challenge->league) {
            'bixos'     => ['bixo'],
            'veteranos' => ['veteran'],
            default     => ['bixo', 'veteran'],
        };
        $eligibleUserIds = BrasileiraoMember::whereIn('role', $eligibleRoles)->pluck('user_id');

        $isTestCases = $challenge->ranking_type === 'test_cases';

        $metricColumn = match ($challenge->ranking_type) {
            'cpu_time'        => 'execution_time',
            'memory_peak'     => 'execution_memory',
            'submission_time' => 'created_at',
            'code_lines'      => 'code_lines',
            'tokens'          => 'num_tokens',
            'test_cases'      => 'num_test_cases',
        };

        $aggregate = $isTestCases ? 'MAX' : 'MIN';

        // Subquery: best metric value per user within the challenge window
        $bestPerUser = Submission::where('problem_id', $challenge->problem_id)
            ->whereBetween('created_at', [$challenge->start_at, $challenge->end_at])
            ->whereIn('user_id', $eligibleUserIds)
            ->when($isTestCases,
                fn ($q) => $q->where('num_test_cases', '>=', 5),
                fn ($q) => $q->where('result', SubmitResult::Accepted)
            )
            ->selectRaw("user_id, {$aggregate}({$metricColumn}) as best_value")
            ->groupBy('user_id');

        // Join back to get the full submission row for that best value (one row per user)
        $leaderboard = Submission::where('submissions.problem_id', $challenge->problem_id)
            ->whereBetween('submissions.created_at', [$challenge->start_at, $challenge->end_at])
            ->whereIn('submissions.user_id', $eligibleUserIds)
            ->when($isTestCases,
                fn ($q) => $q->where('submissions.num_test_cases', '>=', 5),
                fn ($q) => $q->where('submissions.result', SubmitResult::Accepted)
            )
            ->joinSub($bestPerUser, 'best', function ($join) use ($metricColumn) {
                $join->on('submissions.user_id', '=', 'best.user_id')
                    ->whereColumn("submissions.{$metricColumn}", 'best.best_value');
            })
            ->when($isTestCases,
                fn ($q) => $q->orderByDesc('best.best_value'),
                fn ($q) => $q->orderBy('best.best_value')
            )
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

                // Higher-is-better (test_cases): within 5% below the max
                // Lower-is-better (others): within 5% above the min
                $withinTie = $isTestCases
                    ? ($firstValue == 0 || ($firstValue - $value) / $firstValue <= 0.05)
                    : ($firstValue == 0 || abs($value - $firstValue) / $firstValue <= 0.05);

                if ($withinTie) {
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
            ->orderByDesc('bixos_points')
            ->orderByDesc('bixos_count')
            ->get();

        $member = Auth::check()
            ? BrasileiraoMember::where('user_id', Auth::id())->with('institution')->first()
            : null;

        $challenges = BrasileiraoWeeklyChallenge::with('problem')
            ->forLeague('bixos')
            ->orderByDesc('start_at')
            ->get();

        $linkedContests = BrasileiraoContest::with('contest')
            ->whereIn('league', ['bixos', 'all'])
            ->get();

        $contestScoreMap = $this->buildContestScoreMap($linkedContests, 'bixos');

        return view('pages.competition.brasileirao', [
            'institutions'   => $institutions,
            'member'         => $member,
            'league'         => 'bixos',
            'challenges'     => $challenges,
            'linkedContests' => $linkedContests,
            'contestScoreMap' => $contestScoreMap,
        ]);
    }

    public function ligaVeteranos()
    {
        $institutions = BrasileiraoInstitution::withCount(['members', 'bixos', 'veterans', 'coaches'])
            ->having('veterans_count', '>', 0)
            ->orderByDesc('veterans_points')
            ->orderByDesc('veterans_count')
            ->get();

        $member = Auth::check()
            ? BrasileiraoMember::where('user_id', Auth::id())->with('institution')->first()
            : null;

        $challenges = BrasileiraoWeeklyChallenge::with('problem')
            ->forLeague('veteranos')
            ->orderByDesc('start_at')
            ->get();

        $linkedContests = BrasileiraoContest::with('contest')
            ->whereIn('league', ['veteranos', 'all'])
            ->get();

        $contestScoreMap = $this->buildContestScoreMap($linkedContests, 'veteranos');

        return view('pages.competition.brasileirao', [
            'institutions'   => $institutions,
            'member'         => $member,
            'league'         => 'veteranos',
            'challenges'     => $challenges,
            'linkedContests' => $linkedContests,
            'contestScoreMap' => $contestScoreMap,
        ]);
    }

    // Returns: [contest_id => [institution_id => ['problems' => N, 'bonus' => 0|1]]]
    private function buildContestScoreMap($linkedContests, string $league): array
    {
        $roles     = $league === 'bixos' ? ['bixo'] : ['veteran'];
        $memberMap = BrasileiraoMember::whereIn('role', $roles)->pluck('institution_id', 'user_id');

        $result = [];

        foreach ($linkedContests as $bc) {
            $contest    = $bc->contest;
            $contestMap = [];

            $competitors = $contest->competitors()->get();

            // competitor_id → collection of institution_ids (filtered to eligible league)
            $competitorInstitutions = collect();
            foreach ($competitors as $competitor) {
                $instIds = collect();
                if ($contest->individual && $competitor->user_id) {
                    $instId = $memberMap->get($competitor->user_id);
                    if ($instId) {
                        $instIds->push($instId);
                    }
                } elseif ($competitor->team_id) {
                    $teamUserIds = DB::table('team_user')
                        ->where('team_id', $competitor->team_id)
                        ->where('accepted', true)
                        ->pluck('user_id');
                    foreach ($teamUserIds as $uid) {
                        $instId = $memberMap->get($uid);
                        if ($instId) {
                            $instIds->push($instId);
                        }
                    }
                    $instIds = $instIds->unique();
                }
                if ($instIds->isNotEmpty()) {
                    $competitorInstitutions->put($competitor->id, $instIds);
                }
            }

            if ($competitorInstitutions->isEmpty()) {
                $result[$bc->id] = [];
                continue;
            }

            // Problems solved per institution
            $solvedRows = DB::table('competitor_problem')
                ->whereIn('competitor_id', $competitorInstitutions->keys())
                ->where('score', '>', 0)
                ->select('competitor_id', 'problem_id')
                ->get();

            $problemsByInstitution = collect();
            foreach ($solvedRows as $row) {
                foreach ($competitorInstitutions->get($row->competitor_id, collect()) as $instId) {
                    if (! $problemsByInstitution->has($instId)) {
                        $problemsByInstitution->put($instId, collect());
                    }
                    $problemsByInstitution->get($instId)->push($row->problem_id);
                }
            }

            // Top 3 institutions for bonus
            $ranked = $competitors->sortByDesc('score')->sortBy('penality')->values();
            $bonusInstitutionIds = collect();
            foreach ($ranked as $competitor) {
                if ($bonusInstitutionIds->count() >= 3) {
                    break;
                }
                foreach ($competitorInstitutions->get($competitor->id, collect()) as $instId) {
                    if (! $bonusInstitutionIds->contains($instId)) {
                        $bonusInstitutionIds->push($instId);
                    }
                    if ($bonusInstitutionIds->count() >= 3) {
                        break;
                    }
                }
            }

            $allIds = $problemsByInstitution->keys()->merge($bonusInstitutionIds)->unique();
            foreach ($allIds as $instId) {
                $contestMap[$instId] = [
                    'problems' => ($problemsByInstitution->get($instId) ?? collect())->unique()->count(),
                    'bonus'    => $bonusInstitutionIds->contains($instId) ? 1 : 0,
                ];
            }

            $result[$bc->id] = $contestMap;
        }

        return $result;
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

        // All user IDs for these roles across the entire brasileirao (for global best comparison)
        $allRoleUserIds = BrasileiraoMember::whereIn('role', $roles)->pluck('user_id');

        $acceptedDescription = SubmitResult::getDescription(SubmitResult::Accepted);

        foreach ($challenges as $challenge) {
            // challengeUserIds: institution members eligible for this specific challenge
            $challengeUserIds = $eligibleUserIds;

            if ($challengeUserIds->isEmpty()) {
                continue;
            }

            $isTestCases = $challenge->ranking_type === 'test_cases';

            $metricColumn = match ($challenge->ranking_type) {
                'cpu_time'        => 'execution_time',
                'memory_peak'     => 'execution_memory',
                'submission_time' => 'created_at',
                'code_lines'      => 'code_lines',
                'tokens'          => 'num_tokens',
                'test_cases'      => 'num_test_cases',
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

            // For test_cases: any submission with ≥5 test cases qualifies; otherwise only Accepted.
            // Must compare against the description string because the model accessor returns a string.
            $qualified = $isTestCases
                ? $windowSubmissions->where('num_test_cases', '>=', 5)
                : $windowSubmissions->where('result', $acceptedDescription);

            $bestPerUser = $qualified->groupBy('user_id')->map(function ($subs) use ($metricColumn, $challenge, $isTestCases) {
                return $subs->sortBy(function ($s) use ($metricColumn, $challenge, $isTestCases) {
                    $v = $challenge->ranking_type === 'submission_time'
                        ? $s->created_at->diffInSeconds($challenge->start_at)
                        : $s->{$metricColumn};
                    return $isTestCases ? -$v : $v; // negate for higher-is-better
                })->first();
            });

            foreach ($bestPerUser as $uid => $submission) {
                if (isset($stats[$uid])) {
                    $stats[$uid]['solved']++;
                }
            }

            if ($bestPerUser->isNotEmpty()) {
                // Global best is compared within the same league (same roles) so bixos and
                // veterans are ranked independently, matching the scoring job behaviour.
                $aggregate  = $isTestCases ? 'MAX' : 'MIN';
                $globalBest = Submission::where('problem_id', $challenge->problem_id)
                    ->whereBetween('created_at', [$challenge->start_at, $challenge->end_at])
                    ->whereIn('user_id', $allRoleUserIds)
                    ->when($isTestCases,
                        fn ($q) => $q->where('num_test_cases', '>=', 5),
                        fn ($q) => $q->where('result', SubmitResult::Accepted)
                    )
                    ->selectRaw("user_id, {$aggregate}({$metricColumn}) as best_value")
                    ->groupBy('user_id')
                    ->when($isTestCases,
                        fn ($q) => $q->orderByDesc('best_value'),
                        fn ($q) => $q->orderBy('best_value')
                    )
                    ->first();

                if ($globalBest) {
                    $firstValue = $challenge->ranking_type === 'submission_time'
                        ? \Carbon\Carbon::parse($globalBest->best_value)->diffInSeconds($challenge->start_at)
                        : $globalBest->best_value;

                    foreach ($bestPerUser as $uid => $submission) {
                        $value = $challenge->ranking_type === 'submission_time'
                            ? $submission->created_at->diffInSeconds($challenge->start_at)
                            : $submission->{$metricColumn};

                        $withinTie = $isTestCases
                            ? ($firstValue == 0 || ($firstValue - $value) / $firstValue <= 0.05)
                            : ($firstValue == 0 || abs($value - $firstValue) / $firstValue <= 0.05);

                        if ($withinTie && isset($stats[$uid])) {
                            $stats[$uid]['bonus']++;
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
        if (! Auth::check()) {
            return redirect()->route('auth.login', ['provider' => 'github'])
                ->with('info', 'Please log in to register for the Brasileirao.');
        }

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
            ['acronym' => $acronym]
        );

        BrasileiraoMember::create([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'role' => $request->role,
        ]);

        return redirect()->route('competition.brasileirao')
            ->with('success', 'Successfully registered for the Brasileirao de Programacao!');
    }

    public function unsubscribe(Request $request)
    {
        $user = User::findOrFail($request->query('user'));
        $user->update(['email_opt_out' => true]);

        return view('pages.unsubscribe');
    }
}
