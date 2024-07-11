<?php

namespace App\Jobs;

use App\Enums\LanguagesType;
use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Models\Competitor;
use App\Models\SubmitRun;
use App\Models\TestCase;
use App\Services\ExecutorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ExecuteSubmitJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uniqueFor = 86400;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected SubmitRun $submit
    ) {
        $this->onQueue('submit');
    }

    public function uniqueId(): string
    {
        return $this->submit->id;
    }

    private function executeTestCase(ExecutorService $executor, TestCase $testCase, $modifiers)
    {
        $timeLimit = $this->submit->problem->time_limit * $modifiers[0];
        $memoryLimit = $this->submit->problem->memory_limit * $modifiers[1];
        $executor->executeTestCase($testCase, $timeLimit, $memoryLimit);

        if ($testCase->validated) {
            $this->submit->execution_time = max($this->submit->execution_time ?? 0, $executor->execution_time);
            $this->submit->execution_memory = max($this->submit->execution_memory ?? 0, $executor->execution_memory);
        }
        // 16 MB is the margin to work
        if ($executor->execution_memory > $timeLimit + 16) {
            return SubmitResult::MemoryLimit;
        }
        if ($executor->execution_time > $timeLimit) {
            return SubmitResult::TimeLimit;
        }
        if ($executor->retval != 0) {
            // TODO: Um RuntimeError pode ser causado por memory limit, mas não tem como saber nesses casos
            // Buscar solução alternativa
            // Por enquanto funciona em python ok.
            return SubmitResult::RuntimeError;
        } else {

            $executor->testOutputFile($testCase);

            if ($executor->retval != 0) {
                $this->submit->output = implode(PHP_EOL, $executor->output);
                return SubmitResult::WrongAnswer;
            }
        }
        $this->submit->execution_time = max($this->submit->execution_time | 0, $executor->execution_time);
        $this->submit->execution_memory = max($this->submit->execution_memory | 0, $executor->execution_memory);
        return SubmitResult::Accepted;
    }

    private function executeAllTestCases(ExecutorService $executor)
    {
        $num = 0;
        $testCasesRel = [];
        $testCases = $this->submit->problem->testCases()->where('validated', '=', true)->with(['inputfile', 'outputfile'])->get();
        $modifiers = LanguagesType::modifiers()[$this->submit->language];
        foreach ($testCases as $testCase) {
            $result = $this->executeTestCase($executor, $testCase, $modifiers);
            $testCasesRel[$testCase->id] = [
                'result' => $result
            ];
            $this->submit->result = $result;
            dump($this->submit->result);
            if ($result == SubmitResult::Accepted) {
                $num += 1;
            } else {
                break;
            }
        }
        if (($this->submit->result == 'Accepted' || $testCases->count() == 0) &&
            $this->submit->problem->testCases()->where('validated', '=', false)->exists()
        ) {
            // Tenta validar os casos de testes não validados até então...
            try {
                $lock = Cache::lock('validate-testcases-' . $this->submit->problem->id, 60);
                $lock->block(5);
                foreach ($this->submit->problem->testCases()->where('validated', '=', false)->with(['inputfile', 'outputfile'])->get() as $testCase) {
                    $result = $this->executeTestCase($executor, $testCase, $modifiers);
                    $testCasesRel[$testCase->id] = [
                        'result' => $result
                    ];
                    if ($result == SubmitResult::Accepted) {
                        $num += 1;
                        $testCase->validated = true;
                        $testCase->save();
                        // Dispatch Job to check submissions
                        CheckSubmissionsOnProblem::dispatch($this->submit->problem)->delay(now()->addMinutes(60))->afterResponse();
                    }
                }
            } catch (LockTimeoutException $e) {
                // Unable to acquire lock...
            } finally {
                $lock?->release();
            }
            if ($num > 0)
                $this->submit->result = SubmitResult::Accepted;
            else if ($testCases->count() == 0)
                $this->submit->result = SubmitResult::NoTestCase;
            else
                $this->submit->result = SubmitResult::WrongAnswer;
            $this->submit->output = null;
        }
        $this->submit->testCases()->sync($testCasesRel);
        $this->submit->num_test_cases = $num;
    }

    /**
     * Execute the job.
     */
    public function handle(ExecutorService $executor): void
    {

        $file = $this->submit->file;
        $this->submit->status = SubmitStatus::Judging;
        $this->submit->result = SubmitResult::NoResult;
        $this->submit->output = null;
        $this->submit->execution_memory = null;
        $this->submit->execution_time = null;
        $this->submit->save();
        $result = $executor->buildProgram($file, $this->submit->language);
        $this->submit->result = $result;
        if ($result == SubmitResult::NoResult)
            $this->executeAllTestCases($executor);
        else if ($result == SubmitResult::CompilationError) {
            $this->submit->output = implode(PHP_EOL, $executor->output);
        }
        $this->submit->status = SubmitStatus::Judged;
        $this->submit->save();

        if (
            $this->submit->result == SubmitResult::fromValue(SubmitResult::Accepted)->description &&
            $this->submit->problem->scores()->exists()
        ) {
            ScoreSubmitJob::dispatch($this->submit);
        }
        // If this submit is in a contest, do the things
        if ($this->submit->contest) {
            $competidor = Competitor::whereHas('submissions', function ($query) {
                $query->where('submit_run_id', $this->submit->id);
            })->first();
            // Synchronously
            ContestComputeScore::dispatchSync($this->submit, $this->submit->contest, $competidor);
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->submit->status = SubmitStatus::Error;
        $this->submit->result = SubmitResult::Error;
        $this->submit->output = $exception->getTraceAsString();
        $this->submit->save();
    }
}
