<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContestClatification extends Model
{
    public $timestamps = false;
    public $guarded = [];


    public function contest()
    {
        return $this->belongsTo(Contest::class);
    }

    public function competitor()
    {
        return $this->belongsTo(Competitor::class);
    }

    public function problem()
    {
        return $this->belongsTo(Problem::class);
    }
}
