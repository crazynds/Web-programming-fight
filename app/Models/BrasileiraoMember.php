<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrasileiraoMember extends Model
{
    public $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function institution()
    {
        return $this->belongsTo(BrasileiraoInstitution::class, 'institution_id');
    }
}
