<?php

namespace App\Http\Controllers;

use App\Models\BrasileiraoContest;
use App\Models\BrasileiraoInstitution;
use App\Models\BrasileiraoMember;
use App\Models\BrasileiraoWeeklyChallenge;
use App\Models\User;
use App\Services\BrasileiraoLeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrasileiraoController extends Controller
{
    public function __construct(private BrasileiraoLeaderboardService $leaderboard)
    {
        $this->middleware('auth')->only(['register']);
    }

    public function index()
    {
        $member = Auth::check()
            ? BrasileiraoMember::where('user_id', Auth::id())->with('institution')->first()
            : null;

        if (! $member) {
            return redirect()->route('competition.liga.bixos');
        }

        $league = $member->role === 'bixo' ? 'bixos' : 'veteranos';

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

        $leaderboard = $this->leaderboard->challengeLeaderboard($challenge, $challenge->league);

        $bonusSubmissionIds = $leaderboard
            ->filter(fn ($e) => $e->hasBonus)
            ->map(fn ($e) => $e->submission->id)
            ->values();

        // Re-map to collection of submissions with user (preserving view contract)
        $submissions = $leaderboard->map(fn ($e) => $e->submission);

        return view('pages.competition.challenge', [
            'challenge'          => $challenge,
            'leaderboard'        => $submissions,
            'member'             => $member,
            'bonusSubmissionIds' => $bonusSubmissionIds,
        ]);
    }

    public function ligaBixos()
    {
        $institutions = $this->leaderboard->institutionLeaderboard('bixos');

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
            'institutions'    => $institutions,
            'member'          => $member,
            'league'          => 'bixos',
            'challenges'      => $challenges,
            'linkedContests'  => $linkedContests,
            'contestScoreMap' => $contestScoreMap,
        ]);
    }

    public function ligaVeteranos()
    {
        $institutions = $this->leaderboard->institutionLeaderboard('veteranos');

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
            'institutions'    => $institutions,
            'member'          => $member,
            'league'          => 'veteranos',
            'challenges'      => $challenges,
            'linkedContests'  => $linkedContests,
            'contestScoreMap' => $contestScoreMap,
        ]);
    }

    // Returns: [bc_id => [institution_id => ['problems' => N, 'bonus' => 0|1]]]
    private function buildContestScoreMap($linkedContests, string $league): array
    {
        $result = [];
        foreach ($linkedContests as $bc) {
            $data = $this->leaderboard->contestScoreData($bc, $league);
            $result[$bc->id] = $data->map(fn ($e) => [
                'problems' => $e->problems,
                'bonus'    => $e->bonus,
            ])->all();
        }
        return $result;
    }

    public function showInstitution(BrasileiraoInstitution $institution)
    {
        $bixosStats     = $this->leaderboard->institutionMemberStats($institution, 'bixos');
        $veteranosStats = $this->leaderboard->institutionMemberStats($institution, 'veteranos');

        return view('pages.competition.institution', compact('institution', 'bixosStats', 'veteranosStats'));
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
            'role'                => 'required|in:coach,bixo,veteran',
        ]);

        $user = Auth::user();

        if (BrasileiraoMember::where('user_id', $user->id)->exists()) {
            return redirect()->route('competition.brasileirao')
                ->with('error', 'You are already registered for the Brasileirao.');
        }

        $acronym = strtoupper(trim($request->institution_acronym));

        $institution = BrasileiraoInstitution::firstOrCreate(['acronym' => $acronym]);

        BrasileiraoMember::create([
            'user_id'        => $user->id,
            'institution_id' => $institution->id,
            'role'           => $request->role,
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
