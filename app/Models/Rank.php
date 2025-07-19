<?php

namespace App\Models;

use App\Enums\LanguagesType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    public $timestamps = false;

    public $guarded = [];

    protected function language(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => LanguagesType::name(intval($value)),
        );
    }

    public function problem()
    {
        return $this->belongsTo(Problem::class);
    }

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
