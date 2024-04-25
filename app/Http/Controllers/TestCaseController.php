<?php

namespace App\Http\Controllers;

use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Enums\TestCaseType;
use App\Http\Requests\StoreTestCaseRequest;
use App\Http\Requests\UpdateTestCaseRequest;
use App\Jobs\CheckSubmissionsOnProblem;
use App\Jobs\ExecuteSubmitJob;
use App\Models\File;
use App\Models\Problem;
use App\Models\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class TestCaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Problem $problem)
    {
        $testCases = $problem->testCases()
            ->withCount([
                'submitRuns',
                'submitRuns as accepted_runs' => function ($query) {
                    $query->where('submit_run_test_case.result', '=', SubmitResult::Accepted);
                },
                'submitRuns as runtime_error_runs' => function ($query) {
                    $query->where('submit_run_test_case.result', '=', SubmitResult::RuntimeError);
                },
                'submitRuns as memory_limit_runs' => function ($query) {
                    $query->where('submit_run_test_case.result', '=', SubmitResult::MemoryLimit);
                },
                'submitRuns as time_limit_runs' => function ($query) {
                    $query->where('submit_run_test_case.result', '=', SubmitResult::TimeLimit);
                },
                'submitRuns as wrong_answer_runs' => function ($query) {
                    $query->where('submit_run_test_case.result', '=', SubmitResult::WrongAnswer);
                }
            ])
            ->orderBy('position')->get();
        return view('pages.testCase.index', [
            'problem' => $problem,
            'testCases' => $testCases,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Problem $problem)
    {
        $this->authorize('update', $problem);
        return view('pages.testCase.create', [
            'problem' => $problem,
            'testCase' => new TestCase(),
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function show(Problem $problem, TestCase $testCase)
    {
        $this->authorize('update', $problem);
        return view('pages.testCase.show', [
            'problem' => $problem,
            'testCase' => $testCase,
            'input' => $testCase->inputfile->get(),
            'output' => $testCase->outputfile->get()
        ]);
    }

    public function downloadInput(Problem $problem, TestCase $testCase)
    {
        $this->authorize('update', $problem);
        return $testCase->inputFile->download(Str::slug($problem->title) . '_input_' . $testCase->position);
    }

    public function downloadOutput(Problem $problem, TestCase $testCase)
    {
        $this->authorize('update', $problem);
        return $testCase->outputfile->download(Str::slug($problem->title) . '_output_' . $testCase->position);
    }

    public function up(Problem $problem, TestCase $testCase)
    {
        $this->authorize('update', $problem);
        if ($testCase->position < $problem->testCases()->count()) {
            $testCase->position += 1;
            $problem->testCases()
                ->where('position', '=', $testCase->position)
                ->decrement('position');
            $testCase->save();
        }
        return redirect()->route('problem.testCase.index', ['problem' => $problem->id]);
    }
    public function down(Problem $problem, TestCase $testCase)
    {
        $this->authorize('update', $problem);
        if ($testCase->position > 1) {
            $testCase->position -= 1;
            $problem->testCases()
                ->where('position', '=', $testCase->position)
                ->increment('position');
            $testCase->save();
        }
        return redirect()->route('problem.testCase.index', ['problem' => $problem->id]);
    }

    public function publicChange(Problem $problem, TestCase $testCase)
    {
        $this->authorize('update', $problem);
        $testCase->public = !$testCase->public;
        $testCase->save();
        return redirect()->route('problem.testCase.index', ['problem' => $problem->id]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTestCaseRequest $request, Problem $problem)
    {
        $this->authorize('update', $problem);
        $inputs = [];
        foreach ($request->file('inputs') as $file) {
            $inputs[$file->getClientOriginalName()] = $file;
        }
        $outputs = [];
        foreach ($request->file('outputs') as $file) {
            $outputs[$file->getClientOriginalName()] = $file;
        }
        $files = array_intersect(array_keys($inputs), array_keys($outputs));
        $filesToDelete = [];
        DB::transaction(function () use ($problem, $inputs, $outputs, $files) {
            $position = $problem->testCases()->count();
            foreach ($files as $file) {
                $position++;

                $inputFile = File::createFile($inputs[$file], "problems/{$problem->id}/input");

                $outputFile = File::createFile($outputs[$file], "problems/{$problem->id}/output");
                $testCase = $problem->testCases()->updateOrCreate([
                    'name' => $file,
                ], [
                    'type' => TestCaseType::FileDiff,
                    'input_file' => $inputFile->id,
                    'output_file' => $outputFile->id,
                ]);
                if (!$testCase->wasRecentlyCreated) {
                    $testCase->position = $testCase->getOriginal('position');
                    $input = $testCase->getOriginal('input_file');
                    $output = $testCase->getOriginal('output_file');
                    $filesToDelete[] = $input;
                    $filesToDelete[] = $output;
                    // Clear test who ran in this testcase 
                    $testCase->validated = false;
                    $testCase->submitRuns()->sync([]);

                    $testCase->save();
                    $position--;
                    Cache::forget('input_' . $testCase->id);
                    Cache::forget('output_' . $testCase->id);
                } else {
                    $testCase->position = $position;
                    $testCase->save();
                }
            }
        });
        CheckSubmissionsOnProblem::dispatch($problem)->afterResponse();
        foreach (File::whereIn('id', $filesToDelete)->lazy() as $file) {
            $file->delete();
        }
        return redirect()->route('problem.testCase.index', ['problem' => $problem->id]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Problem $problem, TestCase $testCase)
    {
        $this->authorize('update', $problem);
        DB::transaction(function () use ($problem, $testCase) {
            foreach ($testCase->submitRuns()->wherePivot('result', '!=', SubmitResult::Accepted)->lazy() as $run) {
                $run->status = SubmitStatus::WaitingInLine;
                $run->result = SubmitResult::NoResult;
                $run->save();
                ExecuteSubmitJob::dispatch($run)->onQueue('submit')->afterCommit();
            }
            $testCase->delete();
            $problem->testCases()
                ->where('position', '>', $testCase->position)
                ->decrement('position');
        });

        // Dispatch Job to check submissions
        CheckSubmissionsOnProblem::dispatch($problem)->afterResponse();
        return redirect()->route('problem.testCase.index', ['problem' => $problem->id]);
    }
}
