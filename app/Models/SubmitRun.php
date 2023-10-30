<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmitRun extends Model
{
    public $timestamps = false;


    public function file(){
        return $this->belongsTo(File::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

}
