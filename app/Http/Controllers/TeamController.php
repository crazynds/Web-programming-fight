<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TeamController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Team::class, 'team');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var User */
        $user = Auth::user();
        return view('pages.team.index', [
            'teams' => $user->teams()->withPivot(['accepted', 'owner'])->withCount(['members', 'invited'])->get()
        ]);
    }

    public function accept(Team $team)
    {
        Gate::authorize('modifyMembers', $team);
        $user = Auth::user();
        $team->related()->updateExistingPivot($user, [
            'accepted' => true
        ]);
        return redirect()->route('team.index');
    }

    public function leave(Team $team)
    {
        Gate::authorize('leave', $team);
        $user = Auth::user();
        $team->related()->detach($user);
        return redirect()->route('team.index');
    }

    public function deny(Team $team)
    {
        $user = Auth::user();
        $team->related()->detach($user);
        return redirect()->route('team.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        return $this->edit(new Team());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request)
    {
        $data = $request->safe([
            'name',
            'acronym',
            'institution_acronym',
        ]);
        if (isset($data['institution_acronym'])) {
            $data['institution_acronym'] = Str::upper($data['institution_acronym']);
        }
        /** @var User */
        $user = Auth::user();
        $team = new Team($data);
        $team->save();
        $team->members()->attach($user, [
            'owner' => true,
            'accepted' => true
        ]);

        return $this->update($request, $team);
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        return view('pages.team.create', [
            'team' => $team
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreTeamRequest $request, Team $team)
    {
        DB::transaction(function () use ($request, $team) {
            $data = $request->safe([
                'name',
                'acronym',
                'institution_acronym',
            ]);
            if (isset($data['institution_acronym'])) {
                $data['institution_acronym'] = Str::upper($data['institution_acronym']);
            }
            $members = $request->input('membersjson');
            /** @var User */
            $user = Auth::user();
            $team->update($data);
            $team->related()->detach($team->invited);
            $idsAdded = [];
            if ($members) {
                $members = json_decode($members);
                $cont = 0;
                foreach ($members as $member) {
                    // No mysql o == é case insensitive, então fds...
                    $user = User::where('name', Str::lower($member->value))
                        ->orWhere('email', Str::lower($member->value))
                        ->first();

                    if ($user) {
                        $idsAdded[] = $user->id;
                        $team->related()->syncWithoutDetaching([$user->id]);
                        $cont += 1;
                    }
                    if ($cont >= 5) {
                        break;
                    }
                }
            }
            $removeMembers = $team->members()->where('owner', false)->whereNotIn('id', $idsAdded)->get();
            $team->related()->detach($removeMembers);
        });
        return redirect()->route('team.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $team->delete();
        return redirect()->route('team.index');
    }
}
