<?php

namespace App\Http\Controllers\Contest;

use App\Http\Controllers\ContestController;
use App\Http\Controllers\Controller;
use App\Models\Competitor;
use App\Http\Requests\StoreCompetitorRequest;
use App\Http\Requests\UpdateCompetitorRequest;
use App\Services\ContestService;
use Illuminate\Support\Facades\App;

class CompetitorController extends Controller
{

    public function __construct(protected ContestService $contestService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $competitors = $this->contestService->contest->competitors()
            ->withSum('scores', 'score')->get();
        return view('pages.contest.competitor.index', [
            'competitors' => $competitors
        ]);
    }

    public function leaderboard()
    {
        return App::call(
            ContestController::class . '@leaderboard',
            [
                'contest' => $this->contestService->contest
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompetitorRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Competitor $competitor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Competitor $competitor)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompetitorRequest $request, Competitor $competitor)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Competitor $competitor)
    {
        //
    }
}
