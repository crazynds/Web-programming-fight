<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContestRequest;
use App\Models\Contest;
use App\Models\Problem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        return view('pages.contest.index', [
            'contests' => Contest::query()->orderBy('id', 'desc')->get()
        ]);
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
        /** @var User $user */
        $user = Auth::user();
        $data = $request->safe()->only([
            'title', 'is_private', 'password', 'start_time', 'duration', 'blind_time', 'penality',
            'parcial_solution', 'show_wrong_answer', 'description'
        ]);
        $problems = $request->safe()->problems;
        $languages = $request->safe()->languages;

        $data['user_id'] = $user->id;

        DB::beginTransaction();

        $data['langs'] = $languages;
        $contest = Contest::create($data);
        $contest->problems()->sync($problems);

        DB::commit();

        return redirect()->route('contest.show', ['contest' => $contest->id]);
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
        /** @var User $user */
        $user = Auth::user();
        if ($user->isAdmin()) {
            $problems = Problem::all();
        } else {
            $problems = Problem::where('visible', true)->orWhere('user_id', $user->id)->get();
        }
        return view('pages.contest.create', [
            'problems' => $problems,
        ])->with('contest', $contest);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreContestRequest $request, Contest $contest)
    {
        $data = $request->safe()->only([
            'title', 'is_private', 'password', 'start_time', 'duration', 'blind_time', 'penality',
            'parcial_solution', 'show_wrong_answer', 'description'
        ]);
        $problems = $request->safe()->problems;
        $languages = $request->safe()->languages;

        DB::beginTransaction();

        $data['langs'] = $languages;
        $contest->update($data);
        $contest->problems()->sync($problems);

        DB::commit();

        return redirect()->route('contest.show', ['contest' => $contest->id]);
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
