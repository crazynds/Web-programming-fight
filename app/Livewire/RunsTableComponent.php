<?php

namespace App\Livewire;

use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Models\SubmitRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class RunsTableComponent extends Component
{

    public $global = false;
    /** @var SubmitRun[] */
    public $runs = [];
    public $lastId = 0;
    public $waitingToBeJudged = [];

    private function getQuery()
    {
        if ($this->global) {
            $query = SubmitRun::whereHas('problem', function ($query) {
                // Hide not visible problems to global
                $query->where('problems.visible', true);
            });
        } else {
            /** @var User */
            $user = Auth::user();
            $query = $user->submissions();
        }
        return $query->with('user', function ($query) {
            $query->select('id', 'name');
        })
            ->with('problem', function ($query) {
                $query->select('id', 'title');
            })
            ->orderByDesc('id')->limit(100);
    }

    private function setSubmitRun(SubmitRun $run)
    {
        $this->runs[$run->id] = (object) $run->toArray();
        $this->runs[$run->id]->can_update = Gate::check('update', $run);
        $this->runs[$run->id]->can_view = Gate::check('view', $run);
        $this->runs[$run->id]->user = (object) $run->user->toArray();
        $this->runs[$run->id]->problem = (object) $run->problem->toArray();
        unset($this->runs[$run->id]->user_id);
        unset($this->runs[$run->id]->problem_id);
        if ($this->lastId < $run->id)
            $this->lastId = $run->id;
    }

    public function refresh()
    {
        $newests = $this->getQuery()->where('submit_runs.id', '>', $this->lastId)->get();
        foreach ($newests->reverse() as $run) {
            $this->setSubmitRun($run);
        }
        // Add the newests that are waiting to the queue
        foreach ($newests as $run) {
            if ($run->status != 'Error' && $run->status != 'Judged') {
                $this->waitingToBeJudged[$run->id] = 0;
            }
        }

        $idsWaiting = array_keys(array_filter($this->waitingToBeJudged, function ($value) {
            return $value == 0;
        }));
        $runsUpdated = SubmitRun::select('id')->whereIn('id', $idsWaiting)->whereIn('status', [SubmitStatus::Judging])->get();
        foreach ($runsUpdated as $run) {
            $this->runs[$run->id]->status = SubmitStatus::fromValue(SubmitStatus::Judging)->description;
        }
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
            if ($this->waitingToBeJudged[$key] > 5) {
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
        $this->refresh();
        //ksort($this->runs);

        return view('livewire.runs-table-component', [
            'limit' => \Illuminate\Support\Facades\RateLimiter::remaining('resubmission:' . Auth::user()->id, 5),
            'submitRuns' => array_reverse($this->runs),
        ]);
    }
}
