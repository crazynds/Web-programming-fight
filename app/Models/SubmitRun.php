<?php

namespace App\Models;

use App\Enums\LanguagesType;
use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Models\Pivot\CompetitorSubmitRun;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class SubmitRun extends Model
{
    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected function language(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => LanguagesType::name(intval($value)),
        );
    }

    protected function languageRaw(): Attribute
    {
        return Attribute::make(
            get: fn ($vl, $attributes) => intval($attributes['language']),
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => SubmitStatus::fromValue(intval($value))->description,
        );
    }

    protected function output(): Attribute
    {
        return Attribute::make(
            set: function (?string $value) {
                $limit = 1024;
                $value = (isset($value) && strlen($value) > $limit) ? substr($value, 0, $limit).'\n\n(...)' : $value;
                if (is_string($value)) {
                    $value = mb_convert_encoding($value, 'UTF-8');
                }
                $value = trim($value);
                if (strlen($value) == 0) {
                    return null;
                }

                return $value;
            },
            get: fn (?string $value) => $value,
        );
    }

    protected function result(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => SubmitResult::fromValue(intval($value))->description,
        );
    }

    public function problem()
    {
        return $this->belongsTo(Problem::class)->withTrashed();
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function testCases()
    {
        return $this->belongsToMany(TestCase::class);
    }

    public function contest()
    {
        return $this->belongsTo(Contest::class);
    }

    public function competitor()
    {
        return $this->hasOneThrough(Competitor::class, CompetitorSubmitRun::class, 'submit_run_id', 'id', 'id', 'competitor_id');
    }
}
