<?php

namespace App\Jobs;

use App\Mail\BrasileiraoNewsletter;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBrasileiraoNewsletterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const BATCH_SIZE = 10;

    public function __construct(
        public string $subject,
        public string $body,
        public int $lastId = 0,
    ) {
        $this->onQueue('email');
    }

    public function handle(): void
    {
        $users = User::where('email_opt_out', false)
            ->whereNotNull('email')
            ->where('id', '>', $this->lastId)
            ->orderBy('id')
            ->take(self::BATCH_SIZE)
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            Mail::to($user->email)->send(new BrasileiraoNewsletter($user, $this->subject, $this->body));
        }

        // Re-dispatch for the next batch
        self::dispatch($this->subject, $this->body, $users->last()->id)->delay(now()->addMinutes(5))->onQueue('email');
    }
}
