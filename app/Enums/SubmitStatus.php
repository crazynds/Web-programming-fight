<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SubmitStatus extends Enum
{
    // Created the submission
    const Submitted = 0;

    // Waiting in the task line
    const WaitingInLine = 1;

    // Is executing
    const Judging = 2;

    // The result is ready
    const Judged = 3;

    // Is detecting the language
    const DetectingLang = 4;

    // Awaiting judges to avaliate in admin panel
    const AwaitingAdminJudge = 5;

    // Error on some of the steps
    const Error = 100;
}
