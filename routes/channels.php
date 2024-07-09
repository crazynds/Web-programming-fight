<?php

use App\Broadcasting\CompetitorContestSubmissionChannel;
use App\Broadcasting\GlobalContestSubmissionChannel;
use App\Models\Contest;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

Broadcast::channel('submissions', function ($user) {
    return true;
});
Broadcast::channel('contest.submissions.{contest}.{competitor}', CompetitorContestSubmissionChannel::class);
Broadcast::channel('contest.submissions.{contest}', GlobalContestSubmissionChannel::class);
