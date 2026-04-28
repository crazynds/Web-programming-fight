<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrasileiraoWeeklyChallenge extends Model
{
    public $guarded = [];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function problem()
    {
        return $this->belongsTo(Problem::class);
    }

    public function isActive(): bool
    {
        return now()->between($this->start_at, $this->end_at);
    }

    public function scopeActive($query)
    {
        return $query->where('start_at', '<=', now())->where('end_at', '>=', now());
    }

    public function rankingLabel(): string
    {
        return match($this->ranking_type) {
            'cpu_time'        => 'CPU Time (lower is better)',
            'memory_peak'     => 'Memory Peak (lower is better)',
            'submission_time' => 'Submission Time (lower is better)',
            'code_lines'      => 'Code Lines (lower is better)',
            'tokens'          => 'Tokens (lower is better)',
            'test_cases'      => 'Test Cases Passed (higher is better, min. 5)',
        };
    }

    public function scopeForLeague($query, string $league)
    {
        return $query->where(function ($q) use ($league) {
            $q->where('league', $league)->orWhere('league', 'all');
        });
    }
}
