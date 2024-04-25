<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Problem extends Model
{
    use SoftDeletes;
    public $guarded = [];


    public function testCases()
    {
        return $this->hasMany(TestCase::class);
    }
    public function submissions()
    {
        return $this->hasMany(SubmitRun::class);
    }
    public function scorers()
    {
        return $this->hasMany(Scorer::class);
    }
    public function ranks($category = null)
    {
        if ($category != null) return $this->hasMany(Rank::class)->where('category', $category);
        return $this->hasMany(Rank::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
