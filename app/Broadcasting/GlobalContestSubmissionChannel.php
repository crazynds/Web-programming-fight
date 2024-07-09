<?php

namespace App\Broadcasting;

use App\Models\Contest;
use App\Models\User;

class GlobalContestSubmissionChannel
{
    /**
     * Create a new channel instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user, Contest $contest): array|bool
    {
        return true;
    }
}
