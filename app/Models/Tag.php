<?php

namespace App\Models;

use App\Observers\TagObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(TagObserver::class)]
class Tag extends Model
{
    public $timestamps = false;
    public $guarded = [];


    public function problems()
    {
        return $this->belongsToMany(Problem::class, 'problem_tag', 'tag_id', 'problem_id');
    }
}
