<?php

namespace App\Livewire;

use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Models\Contest;
use App\Models\SubmitRun;
use App\Models\User;
use App\Services\ContestService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class RunsTableComponent extends Component
{

    public $global = false;
    /** @var SubmitRun[] */
    public $runs = [];
    public $waitingToBeJudged = [];
    public $lastCheck = 0;
    public Contest|null $contest = null;
    protected ContestService $contestService;
    
    public function boot(ContestService $contestService)
    {
        $this->contestService = $contestService;
        if ($contestService->inContest)
            $this->contest = $contestService->contest; 
           
        $newests = $this->getQuery()->get();
        $updateStatus = array_map(fn (int $val) => SubmitStatus::getDescription($val),[SubmitStatus::Judged, SubmitStatus::Error]);
        foreach ($newests->reverse() as $run) {
            $this->setSubmitRun($run);
            if (!in_array($run->status,$updateStatus)) {
                $this->waitingToBeJudged[$run->id] = 0;
            }
        }
    }

    private function getQuery()
    {
        if ($this->global) {
            if ($this->contest) {
                $contest = $this->contest;
                $query = $contest
                    ->submissions()
                    ->with(['competitor', 'contest']);

                if ($contest->endTime()->addMinutes(5)->gt(now()))
                    $query->where('submit_runs.created_at', '<', $contest->blindTime());
            } else {
                $query = SubmitRun::whereHas('problem', function ($query) {
                    // Hide not visible problems to global
                    $query->where('problems.visible', true);
                })->where('contest_id', null);
            }
        } else {
            if ($this->contestService->inContest) {
                $query = $this->contestService->competitor->submissions()
                    ->with('contest');
            } else {
                /** @var User */
                $user = Auth::user();
                $query = $user->submissions()->where('contest_id', null);
            }
        }
        if (!$this->contest)
            $query->with('user', function ($query) {
                $query->select('id', 'name');
            });
        else
            $query->with('competitor', function ($query) {
                $query->select('id', 'acronym');
            });
        /** @var User */
        $user = Auth::user();
        if (!$user->isAdmin())
            $query->limit(100);

        return $query
            ->with('problem', function ($query) {
                $query->select('id', 'title');
            })
            ->orderByDesc('id');
    }

    private function setSubmitRun(SubmitRun $run)
    {
        $this->runs[$run->id] = (object) $run->toArray();
        $this->runs[$run->id]->can_update = Gate::check('update', $run);
        $this->runs[$run->id]->can_view = Gate::check('view', $run);
        $this->runs[$run->id]->problem = (object) $run->problem->toArray();
        $this->runs[$run->id]->name = $run->competitor?->acronym ?? $run->user->name;
        unset($this->runs[$run->id]->user_id);
        unset($this->runs[$run->id]->problem_id);
        $this->lastCheck = max($run->updated_at,$this->lastCheck);
    }

    public function refresh()
    {
        $newests = $this->getQuery()->whereDate('submit_runs.updated_at', '>=', $this->lastCheck)->get();
        $updateStatus = array_map(fn (int $val) => SubmitStatus::getDescription($val),[SubmitStatus::Judged, SubmitStatus::Error]);
        foreach ($newests->reverse() as $run) {
            if(!isset($this->runs[$run->id])){
                $this->waitingToBeJudged[$run->id] = 0;
            }
            $this->setSubmitRun($run);
            if (!in_array($run->status,$updateStatus)) {
                $this->waitingToBeJudged[$run->id] = 0;
            }
        }
        $idsWaiting = array_keys(array_filter($this->waitingToBeJudged, function ($value) {
            return $value == 0;
        }));
        $runsUpdated = SubmitRun::select('id', 'result', 'num_test_cases')->whereIn('id', $idsWaiting)->whereIn('status', [SubmitStatus::Judged, SubmitStatus::Error])->get();
        foreach ($runsUpdated as $run) {
            $this->runs[$run->id]->status = SubmitStatus::fromValue(SubmitStatus::Judged)->description;
            $this->waitingToBeJudged[$run->id] = 1;
            if (
                $run->result == SubmitResult::fromValue(SubmitResult::CompilationError)->description ||
                ($run->result != SubmitResult::fromValue(SubmitResult::Accepted)->description && $run->num_test_cases <= 5)
            ) {
                $this->waitingToBeJudged[$run->id] = 6;
            }
        }
        foreach ($this->waitingToBeJudged as $key => $value) {
            if ($value == 0) continue;
            $this->waitingToBeJudged[$key] += 1;
            if ($this->waitingToBeJudged[$key] > 3) {
                $run = SubmitRun::find($key);
                $this->setSubmitRun($run);
                unset($this->waitingToBeJudged[$key]);

                // Run não é minha
                //if ($run->user_id != Auth::user()->id) continue;
                // Bem na real, vou estourar balões em todas as runs mesmo

                if ($run->result == SubmitResult::fromValue(SubmitResult::Accepted)->description) $this->dispatch('myRunAccepted');
                else $this->dispatch('myRunFailed');
            }
        }
    }

    public function render()
    {
        //ksort($this->runs);

        return view('livewire.runs-table-component', [ 
            'limit' => \Illuminate\Support\Facades\RateLimiter::remaining('resubmission:' . Auth::user()->id, 5),
            'submitRuns' => array_reverse($this->runs),
        ]);
    }
}
