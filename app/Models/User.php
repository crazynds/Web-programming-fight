<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public $guarded = [];

    public static function guest(): User
    {
        return once(function () {
            return new User([
                'id' => 0,
                'name' => 'Guest',
                'email' => 'guest@localhost',
                'email_verified_at' => now(),
            ]);
        });
    }

    public function directory()
    {
        return 'users/'.$this->id;
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function isAdmin()
    {
        // return false;
        return $this->id == 1 || $this->email == 'pozzer3@gmail.com' || $this->email == 'lh.lagonds@gmail.com';
    }

    public function problems()
    {
        return $this->hasMany(Problem::class);
    }

    public function contest()
    {
        return $this->hasMany(Contest::class);
    }

    public function lastRun()
    {
        return $this->hasOne(Submission::class)->latestOfMany();
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }

    public function myTeams()
    {
        return $this->belongsToMany(Team::class)->where('owner', true);
    }

    public function teamsInvited()
    {
        return $this->belongsToMany(Team::class)->where('accepted', false);
    }
}
