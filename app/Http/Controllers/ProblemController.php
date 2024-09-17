<?php

namespace App\Http\Controllers;

use App\Enums\SubmitResult;
use App\Http\Requests\StoreProblemRequest;
use App\Models\Contest;
use App\Models\File;
use App\Models\Problem;
use App\Models\Rating;
use App\Models\SubmitRun;
use App\Models\User;
use App\Services\ContestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use ZipArchive;
use Zip;

class ProblemController extends Controller
{

    public function __construct(protected ContestService $contestService)
    {
        $this->authorizeResource(Problem::class, 'problem');
    }

    public function publicChange(Problem $problem)
    {
        $this->authorize('update', $problem);
        $mininun = 3;
        if ($problem->testCases()->where('validated', true)->count() < $mininun) {
            $problem->visible = false;
            $problem->save();
            return redirect()->route('problem.testCase.index', [
                'problem' => $problem->id
            ])->withErrors(['msg' => 'To enable a problem, you need to validate at least ' . $mininun . ' test cases']);
        }
        $problem->visible = !$problem->visible;
        $problem->save();
        return redirect()->route('problem.index');
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($this->contestService->inContest) {
            $problems = $this->contestService->contest->problems()
                ->withCount([
                    'submissions' => function ($query) {
                        $query->where('contest_id', $this->contestService->contest->id);
                    },
                    'submissions as accepted_submissions' => function ($query) {
                        $query->where('submit_runs.result', SubmitResult::Accepted)
                            ->where('contest_id', $this->contestService->contest->id);
                    },
                    'submissions as my_accepted_submissions' => function ($query) {
                        $query->where('submit_runs.result', SubmitResult::Accepted)
                            ->join('competitor_submit_run', 'submit_runs.id', 'competitor_submit_run.submit_run_id')
                            ->where('competitor_submit_run.competitor_id', $this->contestService->competitor->id)
                            ->limit(1);
                    },
                ])
                ->orderBy('id')->get();
        } else {
            $problems = Problem::withCount([
                'submissions',
                'submissions as accepted_submissions' => function ($query) {
                    $query->where('submit_runs.result', SubmitResult::Accepted);
                },
                'submissions as my_accepted_submissions' => function ($query) {
                    $query->where('submit_runs.result', SubmitResult::Accepted)
                        ->where('submit_runs.user_id', Auth::user()->id)
                        ->limit(1);
                },
                'ranks'
            ])
                ->where(function ($query) {
                    /** @var User */
                    $user = Auth::user();
                    if (!$user->isAdmin())
                        $query->where('user_id', $user->id)
                            ->orWhere('visible', true);
                })
                ->orderBy('id');
            if ($request->input('contest')) {
                /** @var Contest */
                $contest = Contest::find($request->input('contest'));
                if ($contest->endTime()->lt(now())) {
                    $problems->join('contest_problem', 'problems.id', 'contest_problem.problem_id')
                        ->where('contest_id', $request->input('contest'));
                }
            }
            $problems = $problems->get();
        }
        return view('pages.problem.index', [
            'problems' => $problems,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.problem.create')->with('problem', new Problem());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProblemRequest $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $data = $request->safe([
            'title',
            'author',
            'time_limit',
            'memory_limit',
            'description',
            'input_description',
            'output_description'
        ]);
        if (!$user->isAdmin()) {
            $data['description'] = strip_tags($data['description']);
            $data['input_description'] = strip_tags($data['input_description']);
            $data['output_description'] = strip_tags($data['output_description']);
        }
        $problem = $user->problems()->create($data);

        return redirect()->route('problem.show', ['problem' => $problem->id]);
    }


    public function podium(Problem $problem)
    {
        Gate::authorize('view', $problem);
        $categories = $problem->ranks()->pluck("category")->unique();
        return view('pages.problem.podium', [
            'problem' => $problem,
            'categories' => $categories
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(Problem $problem)
    {
        if ($this->contestService->inContest && $this->contestService->contest->problems()->where('id', $problem->id)->count() == 0)
            return back();
        if ($this->contestService->inContest)
            $clarifications = $this->contestService->contest->clarifications()->where('problem_id', $problem->id)
                ->where(function ($query) {
                    $query->where('competitor_id', $this->contestService->competitor->id)
                        ->orWhere('public', true);
                })
                ->orderBy('id')->get();
        /** @var User */
        $user = Auth::user();
        $rating = Rating::find([
            'user_id' => $user->id,
            'problem_id' => $problem->id,
        ]);
        return view('pages.problem.show', [
            'problem' => $problem,
            'testCases' => $problem->testCases()->orderBy('position')->where('public', true)->where('validated', true)->get(),
            'clarifications' => $clarifications ?? null,
            'rating' => $rating ?? null,
            'accepted' => $user->submissions()->where('problem_id', $problem->id)->where('result', SubmitResult::Accepted)->exists(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Problem $problem)
    {
        return view('pages.problem.create')->with('problem', $problem);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreProblemRequest $request, Problem $problem)
    {
        /** @var User $user */
        $user = Auth::user();
        $data = $request->safe([
            'title',
            'author',
            'time_limit',
            'memory_limit',
            'description',
            'input_description',
            'output_description'
        ]);
        if (!$user->isAdmin()) {
            if (isset($data['description']))
                $data['description'] = strip_tags($data['description']);
            if (isset($data['input_description']))
                $data['input_description'] = strip_tags($data['input_description']);
            if (isset($data['output_description']))
                $data['output_description'] = strip_tags($data['output_description']);
        }
        $problem->update($data);

        return redirect()->route('problem.show', ['problem' => $problem->id]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Problem $problem)
    {
        $problem->delete();
        return $this->index($request);
    }
}
