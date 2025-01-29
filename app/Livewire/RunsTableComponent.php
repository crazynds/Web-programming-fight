<?php

namespace App\Livewire;

use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Events\UpdateSubmissionEvent;
use App\Models\Contest;
use App\Models\SubmitRun;
use App\Models\User;
use App\Services\ContestService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class RunsTableComponent extends Component
{

    public $global = false;
    public $lastCheck = 0;
    public Contest|null $contest = null;
    protected ContestService $contestService;
    
    public function boot(ContestService $contestService)
    {
        $this->contestService = $contestService;
        if ($contestService->inContest)
            $this->contest = $contestService->contest; 
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


    public function refresh()
    {
        $newests = $this->getQuery()->where('submit_runs.updated_at', '>', $this->lastCheck)->get();
        foreach ($newests as $run) {
            $event = new UpdateSubmissionEvent($run);
            $this->dispatch('updateSubmissionEvent',$event->data);
            $this->lastCheck = max($this->lastCheck,$run->updated_at);
        }
    }

    public function render()
    {
        return view('livewire.runs-table-component', [
            'text' => $this->getQuery()->whereDate('submit_runs.updated_at', '>=', $this->lastCheck)->toSql()
        ]);
    }
}
