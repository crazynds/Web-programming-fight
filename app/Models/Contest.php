<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Contest extends Model
{
    public $guarded = [];

    protected $casts = [
        'langs' => 'array',
        'start_time' => 'datetime',
    ];

    public function status()
    {
        if ($this->start_time->greaterThanOrEqualTo(now())) return 'Open';
        if ($this->start_time->addMinutes($this->duration)->lessThanOrEqualTo(now())) return 'Ended';

        if ($this->start_time->lessThanOrEqualTo(now())) return 'In Progress';

        return 'UNKNOWN';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function problems()
    {
        return $this->belongsToMany(Problem::class, 'contest_problem');
    }
}
