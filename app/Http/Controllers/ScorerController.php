<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScorerRequest;
use App\Models\File;
use App\Models\Problem;
use App\Models\Scorer;
use App\Policies\ScorerPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScorerController extends Controller
{


    /**
     * Display a listing of the resource.
     */
    public function index(Problem $problem)
    {
        $this->authorize('update', $problem);
        return view('pages.scorer.index', [
            'problem' => $problem,
            'scorers' => $problem->scorers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Problem $problem)
    {
        $this->authorize('update', $problem);
        return view('pages.scorer.create', [
            'problem' => $problem,
            'scorer' => new Scorer(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreScorerRequest $request, Problem $problem)
    {
        $this->authorize('update', $problem);

        DB::transaction(function () use ($problem, $request) {
            $data = $request->safe()->all();
            $scorerFile = File::createFile($data['code'], "problems/{$problem->id}/scorer");
            $inputFile = File::createFile($data['input'], "problems/{$problem->id}/scorer/input");

            $problem->scorers()->create([
                'language' => $data['lang'],
                'input_id' => $inputFile->id,
                'file_id' => $scorerFile->id,
                'name' => $data['name'],
                'time_limit' => $data['time_limit'],
                'memory_limit' => $data['memory_limit'],
            ]);
        });
        return redirect()->route('problem.scorer.index', ['problem' => $problem->id]);
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Problem $problem, Scorer $scorer)
    {
        $this->authorize('update', $problem);
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Problem $problem, Scorer $scorer)
    {
        $this->authorize('update', $problem);
        $scorer->delete();
        return redirect()->route('problem.scorer.index', ['problem' => $problem->id]);
    }
}
