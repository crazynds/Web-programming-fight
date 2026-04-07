<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrasileiraoWeeklyChallengeRequest;
use App\Jobs\PublishBrasileiraoChallengeProblemsJob;
use App\Jobs\ScoreBrasileiraoChallengeJob;
use App\Jobs\SendBrasileiraoNewsletterJob;
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

        return view('pages.competition.admin.newsletter', compact('subscriberCount'));
    }

    public function previewNewsletter(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
        ]);

        $request->session()->put('newsletter.subject', $request->input('subject'));
        $request->session()->put('newsletter.body', $request->input('body'));

        $subscriberCount = User::where('email_opt_out', false)->whereNotNull('email')->count();
        $subject = $request->input('subject');
        $body = $request->input('body');
        $previewUser = auth()->user();

        $emailHtml = view('emails.brasileirao-newsletter', [
            'user'         => $previewUser,
            'body'         => $body,
            'emailSubject' => $subject,
        ])->render();

        return view('pages.competition.admin.newsletter-preview', compact('subject', 'body', 'previewUser', 'subscriberCount', 'emailHtml'));
    }

    public function sendNewsletter(Request $request)
    {
        $subject = $request->session()->pull('newsletter.subject');
        $body    = $request->session()->pull('newsletter.body');

        if (!$subject || !$body) {
            return redirect()->route('competition.admin.newsletter')
                ->with('error', 'Session expired. Please fill in the form again.');
        }

        SendBrasileiraoNewsletterJob::dispatch($subject, $body);

        return redirect()->route('competition.admin.index')
            ->with('success', 'Newsletter enqueued for sending.');
    }

}
