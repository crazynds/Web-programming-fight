<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    
     public $guarded = [];

    public function directory(){
        return 'users/'.$this->id;
    }

    public function submissions(){
        return $this->hasMany(SubmitRun::class);
    }

    public function isAdmin(){
        //return false;
        return $this->id==1;
    }

    public function problems(){
        return $this->hasMany(Problem::class);
    }

    public function lastRun(){
        return $this->hasOne(SubmitRun::class)->latestOfMany();
    }

}
