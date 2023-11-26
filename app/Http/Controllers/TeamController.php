<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var User */
        $user = Auth::user();
        return view('pages.team.index',[
            'teams' => $user->teams()->withPivot(['accepted','owner'])->withCount(['members','invited'])->get()
        ]);
    }

    public function accept(Team $team){

        $user = Auth::user();
        $team->related()->updateExistingPivot($user,[
            'accepted' => true
        ]);
        return redirect()->route('team.index');
    }

    public function leave(Team $team){

        $user = Auth::user();
        $team->related()->detach($user);
        return redirect()->route('team.index');
    }

    public function deny(Team $team){

        $user = Auth::user();
        $team->related()->detach($user);
        return redirect()->route('team.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        /** @var User */
        $user = Auth::user();
        $cont = $user->myTeams()->count();
        if($cont > 10){
            return redirect()->route('team.index');
        }
        return $this->edit(new Team());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request)
    {

        DB::transaction(function ()use($request){
            $data = $request->safe([
                'name',
                'acronym',
            ]);
            $members = $request->input('membersjson');
            /** @var User */
            $user = Auth::user();
            $team = new Team($data);
            $team->save();
            $team->members()->attach($user,[
                'owner' => true,
                'accepted' => true
            ]);
            if($members){
                $members = json_decode($members);
                foreach($members as $member){
                    $user = User::where('name',\Str::lower($member->value))->first();

                    if($user){
                        $team->invited()->attach($user);
                    }
                }
            }
        });
        return redirect()->route('team.index');
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
        return view('pages.team.create',[
            'team' => $team
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreTeamRequest $request, Team $team)
    {
        DB::transaction(function ()use($request,$team){
            $data = $request->safe([
                'name',
                'acronym',
            ]);
            $members = $request->input('membersjson');
            /** @var User */
            $user = Auth::user();
            $team->update($data);
            $team->related()->detach($team->invited);
            $idsAdded = [];
            if($members){
                $members = json_decode($members);
                foreach($members as $member){
                    $user = User::where('name',\Str::lower($member->value))->first();

                    if($user){
                        $idsAdded[] = $user->id;
                        $team->related()->attach($user);
                    }
                }
            }
            $removeMembers = $team->members()->where('owner',false)->whereNotIn('id',$idsAdded)->get();
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
