<?php

namespace App\View\Components;

use App\Models\Contest;
use App\Models\SubmitRun;
use App\Models\User;
use App\Services\ContestService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class RunsTable extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public bool $global,
        public Contest|null $contest,
        protected ContestService $contestService,
    ) {
    }

    private function getQuery()
    {
        if ($this->global) {
            if ($this->contest) {
                $contest = $this->contest;
                $query = $contest
                    ->submissions()
                    ->with('competitor');

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
                $query = $this->contestService->competitor->submissions();
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
            $query->with('competidor', function ($query) {
                $query->select('id', 'acronym');
            });
        /** @var User */
        $user = Auth::user();
        if (!$user->isAdmin())
            $query->limit(300);

        return $query
            ->with('problem', function ($query) {
                $query->select('id', 'title');
            })
            ->orderByDesc('id');
    }

    private function getChannel()
    {
        if ($this->contestService->inContest) {
            if ($this->global)
                return 'contest.submissions.' . $this->contest->id;
            else
                return 'contest.submissions.' . $this->contest->id . '.' . $this->contestService->competitor->id;
        }
        return 'submissions';
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.runs-table', [
            'limit' => \Illuminate\Support\Facades\RateLimiter::remaining('resubmission:' . Auth::user()->id, 5),
            'submitRuns' => $this->getQuery()->get(),
            'channel' => $this->getChannel()
        ]);
    }
}
