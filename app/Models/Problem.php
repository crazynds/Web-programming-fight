<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Problem extends Model
{
    use SoftDeletes;
    public $guarded = [];


    public function testCases(){
        return $this->hasMany(TestCase::class);
    }
    public function submitions(){
        return $this->hasMany(SubmitRun::class);
    }
}
