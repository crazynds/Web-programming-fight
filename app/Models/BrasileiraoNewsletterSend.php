<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrasileiraoNewsletterSend extends Model
{
    public $guarded = [];

    public function newsletter()
    {
        return $this->belongsTo(BrasileiraoNewsletter::class, 'newsletter_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
