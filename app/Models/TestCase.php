<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestCase extends Model
{
    public $guarded = [];

    public $casts = [
        'public' => 'boolean',
        'rankeable' => 'boolean',
        'validated' => 'boolean',
    ];

    public $timestamps = false;

    public function problem()
    {
        return $this->belongsTo(Problem::class);
    }

    public function inputfile()
    {
        return $this->belongsTo(File::class, 'input_file');
    }

    public function outputfile()
    {
        return $this->belongsTo(File::class, 'output_file');
    }

    public function submissions()
    {
        return $this->belongsToMany(Submission::class);

    }

    public function submitRuns()
    {
        return $this->submissions();
    }
}
