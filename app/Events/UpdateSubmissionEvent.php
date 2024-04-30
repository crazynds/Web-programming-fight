<?php

namespace App\Events;

use App\Models\SubmitRun;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateSubmissionEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;

    /**
     * Create a new event instance.
     */
    public function __construct(public SubmitRun $submitRun)
    {
        $submitRun->refresh();
        $this->data = [
            'id' => $submitRun->id,
            'datetime' => \Carbon\Carbon::parse($submitRun->created_at)->format('H:i:s'),
            'user' => $submitRun->user->name,
            'problem' => [
                'title' => $submitRun->problem->title,
                'id' => $submitRun->problem->id,
            ],
            'language' => $submitRun->language,
            'status' => $submitRun->status,
            'result' => $submitRun->result,
            'testCases' => $submitRun->status != 'Judged' ? '---' : $submitRun->num_test_cases,
            'resources' => ((isset($submitRun->execution_time) && $submitRun->status == 'Judged') ? number_format($submitRun->execution_time / 1000, 2, '.', ',') . 's' : '--') . ' | ' . ((isset($submitRun->execution_memory) && $submitRun->status == 'Judged') ? $submitRun->execution_memory . ' MB' : '--'),
            'suspense' => ($submitRun->status == 'Judged' ? ($submitRun->num_test_cases + 1) / ($submitRun->problem->testCases()->count() + 1) : 0) > 0.4,
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
