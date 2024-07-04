<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompetitorRequest;
use App\Http\Requests\StoreContestRequest;
use App\Models\Contest;
use App\Models\Problem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class ContestController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Contest::class, 'contest');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contests = Contest::orderBy('start_time', 'desc')
            ->where('user_id', Auth::user()->id)
            ->orWhereDate('start_time', '>=', now()->subDays(3))->get();
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
        $competitor = $contest->checkCompetitor($user);
        if (!$competitor)
            return Redirect::back()->withErrors(['contest' => 'You are\'nt participating this contest.']);
        session()->put('contest', [
            'contest' => $contest->id,
            'competitor' => $competitor->id,
        ]);
        return redirect()->route('home');
    }

    public function join(StoreCompetitorRequest $request, Contest $contest)
    {
        /** @var User $user */
        $user = Auth::user();
        if ($contest->is_private && $request->input('password') != $contest->password) {
            return Redirect::back()->withErrors(['password' => 'Wrong password']);
        }

        if ($contest->start_time->addMinutes($contest->duration)->lt(now())) {
            return Redirect::back()->withErrors(['contest' => 'Contest is over! Try join on other contest.']);
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
            $team = $user->myTeams()->where('team_id', $team)->first();
            if (!$team)
                return Redirect::back()->withErrors(['team' => 'Please, select a team that you are the owner of.']);

            $contest->competitors()->create([
                'team_id' => $team->id,
                'name' => $team->name,
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
            'competitor' => $contest->checkCompetitor(Auth::user()),
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
