<?php

namespace App\Console\Commands;

use App\Mail\BrasileiraoNewsletter;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestNewsletterEmail extends Command
{
    protected $signature = 'newsletter:test {email}';
    protected $description = 'Send a test newsletter email to the given address';

    public function handle(): void
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first()
            ?? new User(['id' => 0, 'name' => 'Test User', 'email' => $email]);

        $subject = '[TEST] Brasileirão de Programação Newsletter';
        $body    = "This is a test email sent via the newsletter:test command.\n\nIf you received this, the email configuration is working correctly.";

        Mail::to($email)->send(new BrasileiraoNewsletter($user, $subject, $body));

        $this->info("Test email sent to {$email}.");
    }
}
