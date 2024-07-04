<?php

namespace App\Services;

use App\Models\Competitor;
use App\Models\Contest;

class ContestService
{

    /** @var Contest */
    public $contest;

    /** @var Competitor */
    public $competitor;

    public $inContest = false;
    public $started = false;

    public function __construct()
    {
        $this->inContest = false;
        $this->started = false;
    }


    public function setContestCompetitor(Contest $contest, Competitor $competitor)
    {
        $this->contest = $contest;
        $this->competitor = $competitor;
        $this->inContest = true;
        $this->started = $contest->start_time->lt(now()) && $contest->start_time->addMinutes($contest->duration)->gt(now());
    }
}
