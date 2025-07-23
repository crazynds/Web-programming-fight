<?php

namespace App\Livewire;

use App\Enums\SubmitStatus;
use App\Events\UpdateSubmissionEvent;
use App\Models\Contest;
use App\Models\Submission;
use App\Models\User;
use App\Services\ContestService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SyncSubmissionComponent extends Component
{
    public $global = false;

    public $lastCheck = 0;

    public ?Contest $contest = null;

    protected ContestService $contestService;

    public function boot(ContestService $contestService)
    {
        $this->contestService = $contestService;
        if ($contestService->inContest) {
            $this->contest = $contestService->contest;
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

                if ($contest->endTime()->addMinutes(5)->gt(now())) {
                    $query->where('submissions.created_at', '<', $contest->blindTime());
                }
            } else {
                $query = Submission::whereHas('problem', function ($query) {
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
        if (! $this->contest) {
            $query->with('user', function ($query) {
                $query->select('id', 'name');
            });
        } else {
            $query->with('competitor', function ($query) {
                $query->select('id', 'acronym');
            });
        }
        /** @var User */
        $user = Auth::user();
        if (! $user->isAdmin()) {
            $query->limit(100);
        }

        return $query
            ->with('problem', function ($query) {
                $query->select('id', 'title');
            })
            ->orderByDesc('id');
    }

    public function refresh()
    {
        $newests = $this->getQuery()->where('submissions.updated_at', '>', $this->lastCheck)->get();
        foreach ($newests as $run) {
            if ($run->status == SubmitStatus::getDescription(SubmitStatus::AwaitingAdminJudge)) {
                continue;
            }
            $event = new UpdateSubmissionEvent($run);
            $this->dispatch('updateSubmissionEvent', $event->data);
            $this->lastCheck = max($this->lastCheck, $run->updated_at);
        }
    }

    public function render()
    {
        return view('livewire.sync-submission-component', [
            'text' => $this->getQuery()->whereDate('submissions.updated_at', '>=', $this->lastCheck)->toSql(),
        ]);
    }
}
