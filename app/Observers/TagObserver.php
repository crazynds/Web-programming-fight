<?php

namespace App\Observers;

use App\Models\Tag;

class TagObserver
{

    /**
     * Handle the File "created" event.
     */
    public function creating(Tag $tag): void
    {
        if (!$tag->alias) {
            $tag->alias = strtolower($tag->name);
        }
    }
}
