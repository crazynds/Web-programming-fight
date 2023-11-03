<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProblemRequest;
use App\Http\Requests\UpdateProblemRequest;
use App\Models\Problem;

class ProblemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.problem.index',[
            'problems' => Problem::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.problem.create')->with('problem',new Problem());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProblemRequest $request)
    {
        $problem = Problem::create($request->safe([
            'title','author','time_limit','memory_limit','description','input_description','output_description'
        ]));

        return redirect()->route('problem.show',['problem' => $problem->id]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Problem $problem)
    {
        return view('pages.problem.show')->with('problem',$problem);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Problem $problem)
    {
        return view('pages.problem.create')->with('problem',$problem);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreProblemRequest $request, Problem $problem)
    {
        $problem->update($request->safe([
            'title','author','time_limit','memory_limit','description','input_description','output_description'
        ]));

        return redirect()->route('problem.show',['problem' => $problem->id]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Problem $problem)
    {
        $problem->delete();
        return $this->index();
    }
}
