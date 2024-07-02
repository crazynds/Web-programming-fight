<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    public $timestamps = false;
    public $guarded = [];


    public function related()
    {
        return $this->belongsToMany(User::class);
    }

    public function members()
    {
        return $this->related()->where('accepted', true);
    }
    public function owner()
    {
        return $this->related()->where('owner', true);
    }
    public function invited()
    {
        return $this->related()->where('accepted', false);
    }

    public function membersjson()
    {
        $arr = [];
        foreach ($this->members()->where('owner', false)->get() as $member) {
            $m = [
                'value' => $member->name,
                'color' => 'green',
                'disabled' => false,
            ];
            $arr[] = $m;
        }
        foreach ($this->invited as $member) {
            $m = [
                'value' => $member->name,
            ];
            $arr[] = $m;
        }
        return json_encode($arr);
    }
}
