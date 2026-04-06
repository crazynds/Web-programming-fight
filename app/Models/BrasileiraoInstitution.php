<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrasileiraoInstitution extends Model
{
    public $guarded = [];

    public function members()
    {
        return $this->hasMany(BrasileiraoMember::class, 'institution_id');
    }

    public function bixos()
    {
        return $this->members()->where('role', 'bixo');
    }

    public function veterans()
    {
        return $this->members()->where('role', 'veteran');
    }

    public function coaches()
    {
        return $this->members()->where('role', 'coach');
    }
}
