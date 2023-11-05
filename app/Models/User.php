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

    public function submitions(){
        return $this->hasMany(SubmitRun::class);
    }

}
