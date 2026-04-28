<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrasileiraoContest extends Model
{
    public $guarded = [];

    public $casts = [
        'scored_at' => 'datetime',
    ];

    public function contest()
    {
        return $this->belongsTo(Contest::class);
    }

    public function leagueLabel(): string
    {
        return match ($this->league) {
            'bixos'     => 'Liga dos Bixos',
            'veteranos' => 'Liga dos Veteranos',
            default     => 'Ambas as ligas',
        };
    }
}
