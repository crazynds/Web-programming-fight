<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Contest extends Pivot
{
    public $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
