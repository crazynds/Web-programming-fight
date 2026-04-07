<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrasileiraoWeeklyChallengeRequest;
use App\Jobs\PublishBrasileiraoChallengeProblemsJob;
use App\Jobs\ScoreBrasileiraoChallengeJob;
use App\Jobs\SendBrasileiraoNewsletterJob;
use App\Models\BrasileiraoNewsletter;
use App\Models\BrasileiraoNewsletterSend;
use App\Models\BrasileiraoWeeklyChallenge;
use App\Models\Problem;
use App\Models\User;
use Illuminate\Http\Request;

class BrasileiraoAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:viewPulse');
    }

    public function index()
    {
        $challenges = BrasileiraoWeeklyChallenge::with('problem')
            ->orderByDesc('start_at')
            ->get();

        return view('pages.competition.admin.index', compact('challenges'));
    }

    public function create()
    {
        $problems = Problem::orderBy('id')->get();

        return view('pages.competition.admin.form', compact('problems'));
    }

    public function store(StoreBrasileiraoWeeklyChallengeRequest $request)
    {
        $data = $request->validated();

        $challenge = BrasileiraoWeeklyChallenge::create($data);

        PublishBrasileiraoChallengeProblemsJob::dispatch($challenge)->delay($challenge->start_at);
        ScoreBrasileiraoChallengeJob::dispatch($challenge)->delay($challenge->end_at);

        return redirect()->route('competition.admin.index')
            ->with('success', 'Weekly challenge created successfully.');
    }

    public function edit(BrasileiraoWeeklyChallenge $challenge)
    {
        $problems = Problem::orderBy('id')->get();

        return view('pages.competition.admin.form', compact('challenge', 'problems'));
    }

    public function update(StoreBrasileiraoWeeklyChallengeRequest $request, BrasileiraoWeeklyChallenge $challenge)
    {
        $data = $request->validated();

        $challenge->update($data);

        // Re-dispatch in case end_at changed; the job checks scored_at and end_at before scoring
        ScoreBrasileiraoChallengeJob::dispatch($challenge)->delay($challenge->end_at);

        return redirect()->route('competition.admin.index')
            ->with('success', 'Weekly challenge updated successfully.');
    }

    public function destroy(BrasileiraoWeeklyChallenge $challenge)
    {
        $challenge->delete();

        return redirect()->route('competition.admin.index')
            ->with('success', 'Weekly challenge deleted.');
    }

    public function newsletter()
    {
        $subscriberCount = User::where('email_opt_out', false)->whereNotNull('email')->count();
        $newsletters = BrasileiraoNewsletter::withCount([
            'sends as sent_count'    => fn ($q) => $q->where('status', 'sent'),
            'sends as failed_count'  => fn ($q) => $q->where('status', 'failed'),
            'sends as pending_count' => fn ($q) => $q->where('status', 'pending'),
        ])->orderByDesc('created_at')->get();

        return view('pages.competition.admin.newsletter', compact('subscriberCount', 'newsletters'));
    }

    public function previewNewsletter(Request $request)
    {
        $request->validate([
            'subject'  => 'required|string|max:255',
            'body'     => 'required|string',
            'audience' => 'required|in:all,registered,unregistered,bixos,veteranos',
        ]);

        $request->session()->put('newsletter.subject', $request->input('subject'));
        $request->session()->put('newsletter.body', $request->input('body'));
        $request->session()->put('newsletter.audience', $request->input('audience'));

        $audience = $request->input('audience');
        $subject = $request->input('subject');
        $body = $request->input('body');
        $previewUser = auth()->user();
        $subscriberCount = User::where('email_opt_out', false)->whereNotNull('email')
            ->when($audience === 'registered',   fn ($q) => $q->whereIn('id', \App\Models\BrasileiraoMember::pluck('user_id')))
            ->when($audience === 'unregistered', fn ($q) => $q->whereNotIn('id', \App\Models\BrasileiraoMember::pluck('user_id')))
            ->when($audience === 'bixos',        fn ($q) => $q->whereIn('id', \App\Models\BrasileiraoMember::where('role', 'bixo')->pluck('user_id')))
            ->when($audience === 'veteranos',    fn ($q) => $q->whereIn('id', \App\Models\BrasileiraoMember::where('role', 'veteran')->pluck('user_id')))
            ->count();

        $emailHtml = view('emails.brasileirao-newsletter', [
            'user'         => $previewUser,
            'body'         => $body,
            'emailSubject' => $subject,
        ])->render();

        return view('pages.competition.admin.newsletter-preview', compact('subject', 'body', 'audience', 'previewUser', 'subscriberCount', 'emailHtml'));
    }

    public function sendNewsletter(Request $request)
    {
        $subject  = $request->session()->pull('newsletter.subject');
        $body     = $request->session()->pull('newsletter.body');
        $audience = $request->session()->pull('newsletter.audience', 'all');

        if (!$subject || !$body) {
            return redirect()->route('competition.admin.newsletter')
                ->with('error', 'Session expired. Please fill in the form again.');
        }

        SendBrasileiraoNewsletterJob::createAndDispatch($subject, $body, $audience);

        return redirect()->route('competition.admin.newsletter')
            ->with('success', 'Newsletter enqueued for sending.');
    }

    public function newsletterShow(BrasileiraoNewsletter $newsletter)
    {
        $sends = $newsletter->sends()
            ->with('user')
            ->orderBy('status')
            ->orderBy('user_id')
            ->paginate(50);

        return view('pages.competition.admin.newsletter-show', compact('newsletter', 'sends'));
    }

    public function newsletterResend(BrasileiraoNewsletter $newsletter)
    {
        $failedCount = $newsletter->sends()->where('status', 'failed')->count();

        if ($failedCount === 0) {
            return redirect()->route('competition.admin.newsletter.show', $newsletter)
                ->with('error', 'No failed sends to retry.');
        }

        // Reset failed to pending
        $newsletter->sends()->where('status', 'failed')->update([
            'status'     => 'pending',
            'error'      => null,
            'updated_at' => now(),
        ]);

        SendBrasileiraoNewsletterJob::dispatch($newsletter->id, 0)->onQueue('email');

        return redirect()->route('competition.admin.newsletter.show', $newsletter)
            ->with('success', "Retrying {$failedCount} failed send(s).");
    }

}
