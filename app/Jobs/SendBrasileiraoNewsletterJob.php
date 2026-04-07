<?php

namespace App\Jobs;

use App\Mail\BrasileiraoNewsletter;
use App\Models\BrasileiraoMember;
use App\Models\BrasileiraoNewsletter as NewsletterModel;
use App\Models\BrasileiraoNewsletterSend;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendBrasileiraoNewsletterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const BATCH_SIZE = 50;

    public function __construct(
        public int $newsletterId,
        public int $lastId = 0,
    ) {
        $this->onQueue('email');
    }

    public static function createAndDispatch(string $subject, string $body, string $audience = 'all'): NewsletterModel
    {
        $total = self::audienceQuery($audience)->count();

        $newsletter = NewsletterModel::create([
            'subject' => $subject,
            'body' => $body,
            'audience' => $audience,
            'total' => $total,
        ]);

        self::dispatch($newsletter->id)->onQueue('email');

        return $newsletter;
    }

    private static function audienceQuery(string $audience): Builder
    {
        return User::where('email_opt_out', false)
            ->whereNotNull('email')
            ->when($audience === 'registered', fn ($q) => $q->whereIn('id',
                BrasileiraoMember::pluck('user_id')
            ))
            ->when($audience === 'unregistered', fn ($q) => $q->whereNotIn('id',
                BrasileiraoMember::pluck('user_id')
            ))
            ->when($audience === 'bixos', fn ($q) => $q->whereIn('id',
                BrasileiraoMember::where('role', 'bixo')->pluck('user_id')
            ))
            ->when($audience === 'veteranos', fn ($q) => $q->whereIn('id',
                BrasileiraoMember::where('role', 'veteran')->pluck('user_id')
            ));
    }

    public function handle(): void
    {
        $newsletter = NewsletterModel::findOrFail($this->newsletterId);

        $sends = BrasileiraoNewsletterSend::where('newsletter_id', $newsletter->id)
            ->where('status', 'pending')
            ->where('user_id', '>', $this->lastId)
            ->orderBy('user_id')
            ->take(self::BATCH_SIZE)
            ->with('user')
            ->get();

        // On first batch (lastId = 0 and no pending records yet), create pending records
        if ($this->lastId === 0 && $sends->isEmpty()) {
            $users = self::audienceQuery($newsletter->audience)
                ->orderBy('id')
                ->get(['id']);

            foreach ($users->chunk(500) as $chunk) {
                BrasileiraoNewsletterSend::insert(
                    $chunk->map(fn ($u) => [
                        'newsletter_id' => $newsletter->id,
                        'user_id' => $u->id,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])->all()
                );
            }

            $newsletter->update(['status' => 'sending']);

            // Re-dispatch to start sending
            self::dispatch($newsletter->id, 0)->onQueue('email');

            return;
        }

        if ($sends->isEmpty()) {
            $newsletter->update(['status' => 'completed']);
            return;
        }

        foreach ($sends as $send) {
            try {
                Mail::to($send->user->email)->send(
                    new BrasileiraoNewsletter($send->user, $newsletter->subject, $newsletter->body)
                );
                $send->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
            } catch (Throwable $e) {
                $send->update(['status' => 'failed', 'error' => $e->getMessage()]);
            }
        }

        self::dispatch($newsletter->id, $sends->last()->user_id)
            ->delay(now()->addMinutes(30));
    }

    public function failed(Throwable $_e): void
    {
        $newsletter = NewsletterModel::find($this->newsletterId);

        if (! $newsletter) {
            return;
        }

        $sent    = $newsletter->sends()->where('status', 'sent')->count();
        $total   = $newsletter->total ?: 1;
        $percent = (int) round(($sent / $total) * 100);

        $newsletter->update([
            'status' => "failed at {$percent}%",
        ]);
    }
}
