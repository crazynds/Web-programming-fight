<?php

namespace App\Events;

use App\Models\SubmitRun;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewSubmissionEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data = [];

    /**
     * Create a new event instance.
     */
    public function __construct(SubmitRun $submitRun)
    {
        $submitRun->refresh();
        $this->data = [
            'id' => $submitRun->id,
            'datetime' => \Carbon\Carbon::parse($submitRun->created_at)->format('H:i:s'),
            'user_id' => $submitRun->user->id,
            'user' => $submitRun->user->name,
            'problem' => [
                'title' => $submitRun->problem->title,
                'id' => $submitRun->problem->id,
            ],
            'language' => $submitRun->language,
            'status' => $submitRun->status,
            'result' => $submitRun->result,
            'testCases' => $submitRun->num_test_cases + 1,
            'resources' => ((isset($submitRun->execution_time) && $submitRun->status == 'Judged') ? number_format($submitRun->execution_time / 1000, 2, '.', ',') . 's' : '--') . ' | ' . ((isset($submitRun->execution_memory) && $submitRun->status == 'Judged') ? $submitRun->execution_memory . ' MB' : '--'),
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('submissions'),
        ];
    }
}
