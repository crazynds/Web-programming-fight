<?php

namespace App\Events;

use App\Models\SubmitRun;
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
    public function __construct(SubmitRun $submitRun)
    {
        $submitRun->refresh();
        if ($submitRun->contest_id) {
            $contestData = [
                'contest_id' => $submitRun->contest_id,
                'competitor_id' => $submitRun->competitor?->id,
                'competitor' => $submitRun->competitor->fullName(),
                'blind' => $submitRun->contest->blindTime()->lt(now()) && $submitRun->contest->endTimeWithExtra()->gt(now()),
            ];
        }
        $this->data = [
            'id' => $submitRun->id,
            'datetime' => \Carbon\Carbon::parse($submitRun->created_at)->format('H:i:s'),
            'full_datetime' => \Carbon\Carbon::parse($submitRun->created_at)->format('Y-m-d H:i:s'),
            'user_id' => $submitRun->user->id,
            'user' => $submitRun->user->name,
            'problem' => [
                'title' => $submitRun->problem->title,
                'id' => $submitRun->problem->id,
            ],
            'language' => $submitRun->language,
            'status' => $submitRun->status,
            'result' => $submitRun->result,
            'testCases' => $submitRun->status != 'Judged' ? '---' : $submitRun->num_test_cases + 1,
            'resources' => ((isset($submitRun->execution_time) && $submitRun->status == 'Judged') ? number_format($submitRun->execution_time / 1000, 2, '.', ',').'s' : '--').' | '.((isset($submitRun->execution_memory) && $submitRun->status == 'Judged') ? $submitRun->execution_memory.' MB' : '--'),
            'suspense' => ($submitRun->status == 'Judged' ? ($submitRun->num_test_cases + 1) / ($submitRun->problem->testCases()->count() + 1) : 0) > 0.4,
            'contest' => $submitRun->contest_id ? $contestData : null,
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
