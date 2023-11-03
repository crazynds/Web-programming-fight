<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestCase extends Model
{
    public $guarded = [];
    public $timestamps = false;

    public function problem(){
        return $this->belongsTo(Problem::class);
    }
    public function inputfile(){
        return $this->belongsTo(File::class,'input_file');
    }
    public function outputfile(){
        return $this->belongsTo(File::class,'output_file');
    }
}
