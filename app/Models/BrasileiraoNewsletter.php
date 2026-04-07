<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrasileiraoNewsletter extends Model
{
    public $guarded = [];

    public function sends()
    {
        return $this->hasMany(BrasileiraoNewsletterSend::class, 'newsletter_id');
    }

    public function sentCount(): int
    {
        return $this->sends()->where('status', 'sent')->count();
    }

    public function failedCount(): int
    {
        return $this->sends()->where('status', 'failed')->count();
    }

    public function pendingCount(): int
    {
        return $this->sends()->where('status', 'pending')->count();
    }
}
