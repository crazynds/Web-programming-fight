<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiffRequest;
use App\Models\File;
use App\Models\Problem;
use App\Services\ContestService;

class DiffController extends Controller
{
    public function __construct(protected ContestService $contestService)
    {
        $this->authorizeResource(Problem::class, 'problem');
    }

    public function create(Problem $problem)
    {
        return view('pages.testCase.diff.create', [
            'problem' => $problem,
        ]);
    }

    public function store(StoreDiffRequest $request, Problem $problem)
    {
        $data = $request->safe()->all();

        $diffFile = File::createFile($data['code'], "problems/{$problem->id}/diff");
        if ($problem->diffProgram) {
            $file = $problem->diffProgram;
            $problem->diffProgram()->dissociate();
            $problem->save();
            $file->delete();
        }
        $problem->diffProgram()->associate($diffFile);
        $problem->diff_program_language = $data['lang'];
        $problem->save();

        return redirect()->route('problem.testCase.index', ['problem' => $problem->id]);
    }

    public function destroy(Problem $problem)
    {
        if ($problem->diffProgram) {
            $file = $problem->diffProgram;
            $problem->diffProgram()->dissociate();
            $problem->save();
            $file->delete();
        }

        return redirect()->route('problem.testCase.index', ['problem' => $problem->id]);
    }
}
