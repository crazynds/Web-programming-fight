<?php

namespace App\Http\Controllers;

use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Enums\TestCaseType;
use App\Http\Requests\StoreManualTestCaseRequest;
use App\Http\Requests\StoreTestCaseRequest;
use App\Jobs\CheckSubmissionsOnProblem;
use App\Jobs\ExecuteSubmitJob;
use App\Models\File;
use App\Models\Problem;
use App\Models\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestCaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Problem $problem)
    {
        $testCases = $problem->testCases()
            ->withCount([
                'submissions',
                'submissions as accepted_runs' => function ($query) {
                    $query->where('submission_test_case.result', '=', SubmitResult::Accepted);
                },
                'submissions as runtime_error_runs' => function ($query) {
                    $query->where('submission_test_case.result', '=', SubmitResult::RuntimeError);
                },
                'submissions as memory_limit_runs' => function ($query) {
                    $query->where('submission_test_case.result', '=', SubmitResult::MemoryLimit);
                },
                'submissions as time_limit_runs' => function ($query) {
                    $query->where('submission_test_case.result', '=', SubmitResult::TimeLimit);
                },
                'submissions as wrong_answer_runs' => function ($query) {
                    $query->where('submission_test_case.result', '=', SubmitResult::WrongAnswer);
                },
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
            'testCase' => new TestCase,
        ]);
    }

    public function createManual(Problem $problem)
    {
        $this->authorize('update', $problem);

        return view('pages.testCase.create-manual', [
            'problem' => $problem,
            'testCase' => new TestCase,
        ]);
    }

    public function edit(Problem $problem, TestCase $testCase)
    {
        $this->authorize('update', $problem);

        return view('pages.testCase.create-manual', [
            'problem' => $problem,
            'testCase' => $testCase,
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
            'output' => $testCase->outputfile->get(),
        ]);
    }

    public function downloadInput(Problem $problem, TestCase $testCase)
    {
        $this->authorize('update', $problem);

        return $testCase->inputFile->download(Str::slug($problem->title).'_input_'.$testCase->position);
    }

    public function downloadOutput(Problem $problem, TestCase $testCase)
    {
        $this->authorize('update', $problem);

        return $testCase->outputfile->download(Str::slug($problem->title).'_output_'.$testCase->position);
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
        $testCase->public = ! $testCase->public;
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
        DB::transaction(function () use ($problem, $inputs, $outputs, $files, $filesToDelete) {
            $position = $problem->testCases()->count();
            foreach ($files as $file) {
                $position++;

                $inputFile = File::createFile($inputs[$file], "problems/{$problem->id}/input");
                $outputFile = File::createFile($outputs[$file], "problems/{$problem->id}/output");

                $t = $problem->testCases()->where('name', $file)->first();
                $testCase = $problem->testCases()->updateOrCreate([
                    'name' => $file,
                ], [
                    'type' => TestCaseType::FileDiff,
                    'input_file' => $inputFile->id,
                    'output_file' => $outputFile->id,
                    'validated' => false,
                    'position' => $problem->testCases()->count() + 1,
                ]);

                if ($t) {
                    $testCase->position = $t->position;
                    $filesToDelete[] = $t->input_file;
                    $filesToDelete[] = $t->output_file;

                    // Clear test who ran in this testcase
                    $testCase->validated = false;
                    $testCase->submissions()->sync([]);
                    $testCase->save();

                    Cache::forget('file:input_'.$testCase->id);
                    Cache::forget('file:output_'.$testCase->id);
                }
            }
        });
        CheckSubmissionsOnProblem::dispatch($problem)->delay(now()->addSeconds(300))->afterResponse();
        foreach (File::whereIn('id', $filesToDelete)->lazy() as $file) {
            $file->delete();
        }

        return redirect()->route('problem.testCase.index', ['problem' => $problem->id]);
    }

    public function storeManual(StoreManualTestCaseRequest $request, Problem $problem)
    {
        $this->authorize('update', $problem);

        DB::beginTransaction();
        $data = $request->safe()->all();

        $comparator = fn ($s1, $s2) => str_replace("\r\n", "\n", trim($s1)) == str_replace("\r\n", "\n", trim($s2));

        $filesToDelete = [];
        $t = $problem->testCases()->where('name', $data['name'])->first();
        $changed = ! $t;

        if (! $t || ! $comparator($t->inputfile->get(), $data['input'])) {
            $inputFile = File::createFileByData($data['input'], "problems/{$problem->id}/input");
            if ($t) {
                $filesToDelete[] = $t->input_file;
            }
            $changed = true;
        } else {
            $inputFile = $t->inputfile;
        }
        if (! $t || ! $comparator($t->outputfile->get(), $data['output'])) {
            $outputFile = File::createFileByData($data['output'], "problems/{$problem->id}/output");
            if ($t) {
                $filesToDelete[] = $t->output_file;
            }
            $changed = true;
        } else {
            $outputFile = $t->outputfile;
        }

        $testCase = $problem->testCases()->updateOrCreate([
            'name' => $data['name'],
        ], [
            'type' => TestCaseType::FileDiff,
            'input_file' => $inputFile->id,
            'output_file' => $outputFile->id,
            'explanation' => $data['explanation'] ?? null,
            'validated' => $changed ? false : ($t?->validated ?? false),
            'position' => $t ? $t->position : $problem->testCases()->count() + 1,
        ]);

        if ($t) {
            $testCase->submissions()->sync([]);
            Cache::forget('file:input_'.$testCase->id);
            Cache::forget('file:output_'.$testCase->id);
        }

        foreach (File::whereIn('id', $filesToDelete)->lazy() as $file) {
            $file->delete();
        }
        DB::commit();
        CheckSubmissionsOnProblem::dispatch($problem)->delay(now()->addSeconds(300))->afterResponse();

        return redirect()->route('problem.testCase.index', ['problem' => $problem->id]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Problem $problem, TestCase $testCase)
    {
        $this->authorize('update', $problem);
        DB::transaction(function () use ($problem, $testCase) {
            foreach ($testCase->submissions()->wherePivot('result', '!=', SubmitResult::Accepted)->lazy() as $run) {
                $run->status = SubmitStatus::WaitingInLine;
                $run->result = SubmitResult::NoResult;
                $run->save();
                ExecuteSubmitJob::dispatch($run)->afterCommit();
            }
            $testCase->delete();
            $problem->testCases()
                ->where('position', '>', $testCase->position)
                ->decrement('position');
        });

        // Dispatch Job to check submissions
        CheckSubmissionsOnProblem::dispatch($problem)->delay(now()->addSeconds(300))->afterResponse();

        return redirect()->route('problem.testCase.index', ['problem' => $problem->id]);
    }
}
