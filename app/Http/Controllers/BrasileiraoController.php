<?php

namespace App\Http\Controllers;

use App\Enums\SubmitResult;
use App\Models\BrasileiraoInstitution;
use App\Models\BrasileiraoMember;
use App\Models\BrasileiraoWeeklyChallenge;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $isTestCases = $challenge->ranking_type === 'test_cases';

        $metricColumn = match ($challenge->ranking_type) {
            'cpu_time'        => 'execution_time',
            'memory_peak'     => 'execution_memory',
            'submission_time' => 'created_at',
            'code_lines'      => 'code_lines',
            'test_cases'      => 'num_test_cases',
        };

        $aggregate = $isTestCases ? 'MAX' : 'MIN';

        // Subquery: best metric value per user within the challenge window
        $bestPerUser = Submission::where('problem_id', $challenge->problem_id)
            ->whereBetween('created_at', [$challenge->start_at, $challenge->end_at])
            ->when($isTestCases,
                fn ($q) => $q->where('num_test_cases', '>=', 5),
                fn ($q) => $q->where('result', SubmitResult::Accepted)
            )
            ->selectRaw("user_id, {$aggregate}({$metricColumn}) as best_value")
            ->groupBy('user_id');

        // Join back to get the full submission row for that best value (one row per user)
        $leaderboard = Submission::where('submissions.problem_id', $challenge->problem_id)
            ->whereBetween('submissions.created_at', [$challenge->start_at, $challenge->end_at])
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
            ->orderByDesc('points')
            ->orderByDesc('bixos_count')
            ->get();

        $member = Auth::check()
            ? BrasileiraoMember::where('user_id', Auth::id())->with('institution')->first()
            : null;

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

        $member = Auth::check()
            ? BrasileiraoMember::where('user_id', Auth::id())->with('institution')->first()
            : null;

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

            $isTestCases = $challenge->ranking_type === 'test_cases';

            $metricColumn = match ($challenge->ranking_type) {
                'cpu_time'        => 'execution_time',
                'memory_peak'     => 'execution_memory',
                'submission_time' => 'created_at',
                'code_lines'      => 'code_lines',
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

            // For test_cases: any submission with ≥5 test cases qualifies; otherwise only Accepted
            $qualified = $isTestCases
                ? $windowSubmissions->where('num_test_cases', '>=', 5)
                : $windowSubmissions->where('result', SubmitResult::Accepted);

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
                $allEligibleUserIds = BrasileiraoMember::whereIn('role', $eligibleRoles)->pluck('user_id');
                $aggregate  = $isTestCases ? 'MAX' : 'MIN';
                $globalBest = Submission::where('problem_id', $challenge->problem_id)
                    ->whereBetween('created_at', [$challenge->start_at, $challenge->end_at])
                    ->whereIn('user_id', $allEligibleUserIds)
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

    public function unsubscribe(Request $request)
    {
        $user = User::findOrFail($request->query('user'));
        $user->update(['email_opt_out' => true]);

        return view('pages.unsubscribe');
    }
}
