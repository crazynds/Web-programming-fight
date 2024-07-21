<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompetitorRequest;
use App\Http\Requests\StoreContestRequest;
use App\Models\Contest;
use App\Models\Problem;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

class ContestController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Contest::class, 'contest');
    }

    public function admin(Contest $contest)
    {
        $this->authorize('admin', $contest);
        return view('pages.contest.admin', [
            'contest' => $contest
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contests = Contest::orderBy('start_time', 'desc')
            ->get();
        return view('pages.contest.index', [
            "contests" => $contests
        ]);
    }

    public function enter(Contest $contest)
    {
        if ($contest->start_time->subMinutes(10)->gt(now())) {
            return Redirect::back()->withErrors(['contest' => 'Contest has not started yet.']);
        }

        /** @var User $user */
        $user = Auth::user();
        /** @var Competitor $competitor */
        if (!Gate::allows('enter', $contest))
            return Redirect::back()->withErrors(['contest' => 'You are\'nt participating this contest.']);

        $competitor = $contest->getCompetitor($user);
        $data = [
            'contest' => $contest->id,
            'competitor' => $competitor->id,
        ];
        Cache::set('contest:user:' . $user->id, $data, $contest->endTime());
        return redirect()->route('home');
    }

    public function leave(Contest $contest)
    {
        Cache::forget('contest:user:' . Auth::user()->id);
        return redirect()->route('contest.index');
    }

    public function leaderboard(Contest $contest)
    {
        $key = 'contest:leaderboard:' . $contest->id;
        $problems = $contest->problems()->orderBy('id')->pluck('id');
        $blind = $contest->blindTime()->lt(now()) && $contest->endTimeWithExtra()->gt(now());
        // If is blind time, get the blind leaderboard. (The latest leaderboard loaded)
        if ($blind)
            $competitors = Cache::get($key . ':blind');
        else
            $competitors = Cache::get($key);
        // If any leaderboard could be loaded, retrive it from database.
        if (!$competitors) {
            $query = $contest->competitors()
                ->with('scores')
                ->with('scores.submission')
                ->withSum('scores', 'score')
                ->withSum('scores', 'penality');

            foreach ($problems as $problem) {
                $query->withCount([
                    'submissions as sum_submissions_' . $problem => function ($query) use ($problem, $contest, $blind) {
                        $query->where('submit_runs.problem_id', $problem);
                        if ($blind) {
                            $query->where('submit_runs.created_at', '<', $contest->blindTime());
                        }
                    }
                ]);
            }

            $competitors = $query
                ->orderBy('scores_sum_score', 'desc')
                ->orderBy('scores_sum_penality', 'asc')->get();

            foreach ($competitors as $competitor) {
                $scores = [];
                foreach ($competitor->scores as $score) {
                    $scores[$score->problem_id] = $score;
                }
                $competitor->scores = $scores;
            }
            Cache::put($key, $competitors, now()->addMinutes(5));
            // Freeze this leaderboard for blind
            if ($contest->endTimeWithExtra()->gt(now()))
                Cache::put($key . ':blind', $competitors, $contest->endTimeWithExtra());
        }
        return view('pages.contest.competitor.leaderboard', [
            'competitors' => $competitors,
            'contest' => $contest,
            'problems' => $problems,
            'blind' => $blind,
            'channel' => 'contest.submissions.' . $contest->id,
        ]);
    }

    public function unregister(Contest $contest)
    {
        $competitor = $contest->getCompetitor(Auth::user());
        if (!$competitor) {
            return back()->withErrors([
                'unregister' => 'You are not participating in this contest.'
            ]);
        }
        $competitor->delete();
        return back();
    }

    public function register(StoreCompetitorRequest $request, Contest $contest)
    {
        /** @var User $user */
        $user = Auth::user();
        if ($contest->is_private && $request->input('password') != $contest->password) {
            return Redirect::back()->withErrors(['password' => 'Wrong password']);
        }

        if ($contest->start_time->addMinutes($contest->duration)->lt(now())) {
            return Redirect::back()->withErrors(['contest' => 'Contest is over! Try register on other contest.']);
        }
        // Verificar se o usuário já está inscrito (time ou individual)
        if ($contest->individual) {
            $contest->competitors()->create([
                'user_id' => $user->id,
                'name' => $user->name,
                'acronym' => $user->id,
            ]);
        } else {
            $team = $request->input('team', 0);
            /** @var Team */
            $team = $user->myTeams()->where('team_id', $team)->first();
            if (!$team)
                return Redirect::back()->withErrors(['team' => 'Please, select a team that you are the owner of.']);
            if ($team->members()->count() > 3) {
                return Redirect::back()->withErrors(['team' => 'Only up to 3 members are allowed in a team to participate in this contest.']);
            }

            $members = $team->members;

            /** @var User $user */

            $competitor = $contest->competitors()->with('team.members')->whereHas('team.members', function ($query) use ($members) {
                $query->whereIn('user_id', $members->pluck('id'));
            })->first();
            if ($competitor) {
                foreach ($competitor->team->members as $user) {
                    if ($members->contains('id', $user->id)) {
                        return Redirect::back()->withErrors(['team' => 'The user ' . $user->name . ' is already participating in this contest.']);
                    }
                }
            }

            $contest->competitors()->create([
                'team_id' => $team->id,
                'name' => '[' . ($team->institution_acronym ? $team->institution_acronym : '????') . '] ' . $team->name,
                'acronym' => $team->acronym,
            ]);
        }
        return Redirect::back();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->isAdmin()) {
            $problems = Problem::all();
        } else {
            $problems = Problem::where('visible', true)->orWhere('user_id', $user->id)->get();
        }
        return view('pages.contest.create', [
            'problems' => $problems,
        ])->with('contest', new Contest());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContestRequest $request)
    {
        $data = $request->safe()->except(['problems', 'g-recaptcha-response']);
        /** @var User $user */
        $user = Auth::user();

        DB::beginTransaction();
        /** @var Contest */
        $contest = $user->contest()->create($data);
        $contest->problems()->detach();
        foreach ($request->input('problems') as $key => $id) {
            $contest->problems()->attach($id, ['position' => $key]);
        }
        DB::commit();

        return redirect()->route('contest.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Contest $contest)
    {
        return view('pages.contest.show', [
            'contest' => $contest,
            'competitor' => $contest->getCompetitor(Auth::user()),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contest $contest)
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->isAdmin()) {
            $problems = Problem::all();
        } else {
            $problems = Problem::where('visible', true)->orWhere('user_id', $user->id)->get();
        }
        return view('pages.contest.create', [
            'problems' => $problems,
            'contest' => $contest
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreContestRequest $request, Contest $contest)
    {
        $data = $request->safe()->except(['problems', 'g-recaptcha-response']);
        /** @var User $user */
        $user = Auth::user();

        DB::beginTransaction();
        /** @var Contest */
        $contest->update($data);
        $contest->problems()->detach();
        foreach ($request->input('problems') as $key => $id) {
            $contest->problems()->attach($id, ['position' => $key]);
        }
        DB::commit();
        return redirect()->route('contest.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contest $contest)
    {
        $contest->delete();
        return $this->index();
    }
}
