<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'result' => $this->result,
            'status' => $this->status,
            'testCases' => $this->num_test_cases + 1,
            'suspense' => $this->when($this->status == 'Judged', ($this->num_test_cases + 1) / ($this->problem->testCases()->count() + 1)),
            'execution' => [
                'time' => $this->execution_time,
                'memory' => $this->execution_memory,
            ],
        ];
    }
}
