<?php

namespace App\Http\Controllers;

use App\Enums\SubmitResult;
use App\Http\Requests\StoreProblemRequest;
use App\Models\Problem;
use Illuminate\Support\Facades\Auth;

class ProblemController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Problem::class, 'problem');
    }

    public function publicChange(Problem $problem){
        $this->authorize('update', $problem);
        $problem->visible = !$problem->visible;
        $problem->save();
        return redirect()->route('problem.index');
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $problems = Problem::withCount([
                'submissions',
                'submissions as accepted_submissions' => function($query){
                    $query->where('submit_runs.result','=',SubmitResult::Accepted);
                },
                'submissions as my_accepted_submissions' => function($query){
                    $query->where('submit_runs.result','=',SubmitResult::Accepted)
                        ->where('submit_runs.user_id','=',Auth::user()->id);
                },
            ])
            ->where(function($query){
                /** @var User */
                $user = Auth::user();
                if($user->isAdmin())
                    $query->where('user_id',$user->id)
                        ->orWhere('visible',true);
            })
            ->orderBy('id')->get();
        return view('pages.problem.index',[
            'problems' => $problems,
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
        /** @var User $user */
        $user = Auth::user();
        $data =$request->safe([
            'title','author','time_limit','memory_limit','description','input_description','output_description'
        ]);
        $problem = $user->problems()->create($data);

        return redirect()->route('problem.show',['problem' => $problem->id]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Problem $problem)
    {
        return view('pages.problem.show',[
            'problem' => $problem,
            'testCases' => $problem->testCases()->orderBy('position')->where('public',true)->where('validated',true)->get()
        ]);
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
