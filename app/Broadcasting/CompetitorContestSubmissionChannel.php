<?php

namespace App\Broadcasting;

use App\Models\Competitor;
use App\Models\Contest;
use App\Models\User;
use App\Services\ContestService;

class CompetitorContestSubmissionChannel
{
    /**
     * Create a new channel instance.
     */
    public function __construct(protected ContestService $contestService)
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user, Contest $contest, Competitor $competitor): array|bool
    {
        return $this->contestService->inContest && $this->contestService->competitor->id == $competitor->id;
    }
}
