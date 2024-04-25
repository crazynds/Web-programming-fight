<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    public $timestamps = false;
    public $guarded = [];


    public function problem()
    {
        return $this->belongsTo(Problem::class);
    }


    public function submitRun()
    {
        return $this->belongsTo(SubmitRun::class);
    }
}
