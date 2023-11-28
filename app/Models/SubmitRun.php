<?php

namespace App\Models;

use App\Enums\LanguagesType;
use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmitRun extends Model
{
    const UPDATED_AT = null;
    public $timestamps = true;
    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected function language(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => LanguagesType::name(intval($value)),
        );
    }
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => SubmitStatus::fromValue(intval($value))->description,
        );
    }
    protected function result(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => SubmitResult::fromValue(intval($value))->description,
        );
    }

    public function problem(){
        return $this->belongsTo(Problem::class);
    }

    public function file(){
        return $this->belongsTo(File::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function testCases(){
        return $this->belongsToMany(TestCase::class);
    }

}
