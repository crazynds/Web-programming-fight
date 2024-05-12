<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContestRequest;
use App\Models\Contest;
use App\Models\Problem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.contest.index');
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Contest $contest)
    {
        return view('pages.contest.show')->with('contest', $contest);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contest $contest)
    {
        return view('pages.contest.create')->with('contest', $contest);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreContestRequest $request, Contest $contest)
    {
        //
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
