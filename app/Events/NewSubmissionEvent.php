<?php

namespace App\Events;

use App\Models\Submission;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewSubmissionEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;

    /**
     * Create a new event instance.
     */
    public function __construct(Submission $submission)
    {
        $submission->refresh();
        if ($submission->contest_id) {
            $contestData = [
                'contest_id' => $submission->contest_id,
                'competitor_id' => $submission->competitor?->id,
                'competitor' => $submission->competitor->fullName(),
                // 'team' => [
                //     'country' => $submission->competitor->team->country,
                //     'state' => $submission->competitor->team->state,
                //     'institution_acronym' => $submission->competitor->team->institution_acronym,
                // ],
                'blind' => $submission->contest->blindTime()->lt(now()) && $submission->contest->endTimeWithExtra()->gt(now()),
            ];
        }
        $this->data = [
            'id' => $submission->id,
            'datetime' => \Carbon\Carbon::parse($submission->created_at)->format('H:i:s'),
            'full_datetime' => \Carbon\Carbon::parse($submission->created_at)->format('Y-m-d H:i:s'),
            'user_id' => $submission->user->id,
            'user' => $submission->user->name,
            'problem' => [
                'title' => $submission->problem->title,
                'id' => $submission->problem->id,
            ],
            'language' => $submission->language,
            'status' => $submission->status,
            'result' => $submission->result,
            'testCases' => $submission->status != 'Judged' ? '---' : $submission->num_test_cases + 1,
            'resources' => ((isset($submission->execution_time) && $submission->status == 'Judged') ? number_format($submission->execution_time / 1000, 2, '.', ',').'s' : '--').' | '.((isset($submission->execution_memory) && $submission->status == 'Judged') ? $submission->execution_memory.' MB' : '--'),
            'suspense' => ($submission->status == 'Judged' ? ($submission->num_test_cases + 1) / ($submission->problem->testCases()->count() + 1) : 0) > 0.4,
            'contest' => $submission->contest_id ? $contestData : null,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        if (! is_null($this->data['contest'])) {
            if ($this->data['contest']['blind']) {
                return [
                    new PrivateChannel('contest.submissions.'.$this->data['contest']['contest_id'].'.'.$this->data['contest']['competitor_id']),
                ];
            } else {
                return [
                    new PrivateChannel('contest.submissions.'.$this->data['contest']['contest_id']),
                    new PrivateChannel('contest.submissions.'.$this->data['contest']['contest_id'].'.'.$this->data['contest']['competitor_id']),
                ];
            }
        }

        return [
            new PrivateChannel('submissions'),
        ];
    }
}
