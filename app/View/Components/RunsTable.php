<?php

namespace App\View\Components;

use App\Models\SubmitRun;
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
        protected ContestService $contestService,
    ) {
    }

    private function getQuery()
    {
        if ($this->global) {
            if ($this->contestService->inContest) {
                $query = $this->contestService->contest
                    ->submissions()->with('competitor');
            } else {
                $query = SubmitRun::whereHas('problem', function ($query) {
                    // Hide not visible problems to global
                    $query->where('problems.visible', true);
                });
            }
        } else {
            if ($this->contestService->inContest) {
                $query = $this->contestService->competitor->submissions();
            } else {
                /** @var User */
                $user = Auth::user();
                $query = $user->submissions();
            }
        }
        return $query->with('user', function ($query) {
            $query->select('id', 'name');
        })
            ->with('problem', function ($query) {
                $query->select('id', 'title');
            })
            ->orderByDesc('id')->limit(100);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.runs-table', [
            'limit' => \Illuminate\Support\Facades\RateLimiter::remaining('resubmission:' . Auth::user()->id, 5),
            'submitRuns' => $this->getQuery()->get(),
        ]);
    }
}
