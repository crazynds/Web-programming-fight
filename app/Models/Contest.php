<?php

namespace App\Models;

use App\Observers\ContestObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[ObservedBy(ContestObserver::class)]
class Contest extends Model
{
    public $guarded = [];

    public $casts = [
        'start_time' => 'datetime'
    ];

    protected function languages(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => is_string($value) ? json_decode($value) : $value,
            set: fn (array $value) => json_encode($value),
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function problems()
    {
        return $this->belongsToMany(Problem::class, 'contest_problem');
    }
    public function competitors()
    {
        return $this->hasMany(Competitor::class);
    }
    public function submissions()
    {
        return $this->hasMany(SubmitRun::class);
    }

    public function endTime(): Carbon
    {
        return $this->start_time->addMinutes($this->duration);
    }

    public function blindTime(): Carbon
    {
        return $this->start_time->addMinutes($this->duration - $this->blind_time);
    }

    public function checkCompetitor(User $user)
    {
        if ($this->individual) {
            return $this->competitors()->where('user_id', $user->id)->first();
        } else {
            return $this->competitors()->whereHas('team.owner', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->first();
        }
    }
}
