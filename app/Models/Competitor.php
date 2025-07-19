<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Competitor extends Model
{
    public $timestamps = false;

    public $guarded = [];

    public function fullName()
    {
        return $this->acronym.' '.$this->name;
    }

    public function acronym(): Attribute
    {
        return new Attribute(get: fn ($value) => '['.$value.']');
    }

    public function participant()
    {
        if ($this->contest->individual) {
            return $this->belongsTo(User::class);
        }

        return $this->belongsTo(Team::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function contest()
    {
        return $this->belongsTo(Contest::class);
    }

    public function submissions()
    {
        return $this->belongsToMany(Submission::class);
    }

    public function scores()
    {
        return $this->hasMany(CompetitorScore::class);
    }
}
