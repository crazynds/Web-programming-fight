<?php

namespace App\Models;

use App\Enums\LanguagesType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scorer extends Model
{
    public $timestamps = false;
    public $guarded = [];


    protected function language(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => LanguagesType::name(intval($value)),
        );
    }
    public function input()
    {
        return $this->belongsTo(File::class, 'input_id');
    }


    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function problem()
    {
        return $this->belongsTo(Problem::class);
    }
    public function testCase()
    {
        return $this->belongsTo(TestCase::class);
    }
}
