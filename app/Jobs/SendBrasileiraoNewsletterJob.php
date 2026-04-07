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

    public function __construct(
        public string $subject,
        public string $body,
    ) {}

    public function handle(): void
    {
        User::where('email_opt_out', false)
            ->whereNotNull('email')
            ->each(function (User $user) {
                Mail::to($user->email)->queue(new BrasileiraoNewsletter($user, $this->subject, $this->body));
            });
    }
}
