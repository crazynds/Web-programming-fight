<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    public $timestamps = false;
    public function problems()
    {
        return $this->belongsToMany(Problem::class, 'problem_tag', 'tag_id', 'problem_id');
    }
}
