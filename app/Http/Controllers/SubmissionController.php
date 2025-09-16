<?php

namespace App\Http\Controllers;

use App\Enums\LanguagesType;
use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Events\NewSubmissionEvent;
use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Resources\SubmissionResultResource;
use App\Jobs\ExecuteSubmitJob;
use App\Models\Contest;
use App\Models\File;
use App\Models\Problem;
use App\Models\Submission;
use App\Models\User;
use App\Services\ContestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class SubmissionController extends Controller
{
    public function __construct(protected ContestService $contestService)
    {
        $this->authorizeResource(Submission::class, 'submission');
    }

    public function global(?Contest $contest = null)
    {
        return view('pages.run.index', [
            'global' => true,
            'contest' => $contest,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.run.index', [
            'global' => false,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        /** @var User $user */
        $user = $this->user();

        if ($this->contestService->inContest) {
            $problems = $this->contestService->contest->problems()->get();
        } elseif ($user->isAdmin()) {
            $problems = Problem::all();
        } else {
            $problems = Problem::where('visible', true)->orWhere('user_id', $user->id)->get();
        }

        return view('pages.run.create', [
            'problems' => $problems,
            'selected' => $request->get('problem'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubmissionRequest $request)
    {
        $user = $this->user();
        if (RateLimiter::tooManyAttempts('submission:'.$user->id, 30)) {
            return Redirect::back()->withErrors(['msg' => 'Too many attempts! Wait a moment and try again!']);
        }
        $problem = Problem::findOrFail($request->problem);
        // check if problem exists in the contest
        if ($this->contestService->inContest && ! $this->contestService->contest->problems()->where('problems.id', $problem->id)->exists()) {
            return Redirect::back()->withErrors(['msg' => 'Problem not found. Are you trying to cheat?']);
        }

        // 1 Hour
        RateLimiter::hit('submission:'.$user->id, 60 * 60);
        $run = DB::transaction(function () use ($request, $user, $problem) {

            $originalFile = $request->file('code');
            $run = new Submission;
            $run->language = $request->input('lang');
            $run->problem()->associate($problem);
            $run->user()->associate($user);

            // 4 MB
            if ($originalFile->getSize() > 1024 * 1024 * 4 || ! mb_check_encoding($originalFile->get(), 'UTF-8')) {
                $run->status = SubmitStatus::Judged;
                if ($originalFile->getSize() > 1024 * 1024 * 4) {
                    $run->result = SubmitResult::FileTooLarge;
                } else {
                    $run->result = SubmitResult::InvalidUtf8File;
                }
            } else {
                $file = File::createFile($originalFile, 'users/'.$user->id.'/attempts'.'/'.$request->problem);
                $run->file()->associate($file);
                $run->status = SubmitStatus::WaitingInLine;
            }
            $run->save();
            if ($this->contestService->started && $this->contestService->inContest) {
                $run->contest()->associate($this->contestService->contest);
                $this->contestService->competitor->submissions()->attach($run);
            }
            if ($run->status == SubmitStatus::getDescription(SubmitStatus::WaitingInLine)) {
                if ($run->language == LanguagesType::name(LanguagesType::Auto_detect)) {
                    switch (strtolower($originalFile->getClientOriginalExtension())) {
                        case 'c':
                            $run->language = LanguagesType::C;
                            break;
                        case 'cpp':
                        case 'c++':
                        case 'cxx':
                        case 'cc':
                            $run->language = LanguagesType::CPlusPlus;
                            break;
                        case 'py':
                            $run->language = LanguagesType::PyPy3_11;
                            break;
                        case 'java':
                        case 'class':
                        case 'jar':
                            $run->language = LanguagesType::Java_OpenJDK24;
                            break;
                        case 'kt':
                        default:
                            $run->language = LanguagesType::BINARY;
                            $run->result = SubmitResult::LanguageNotSupported;
                            $run->status = SubmitStatus::Judged;
                            $file = $run->file;
                            $run->file_id = null;
                            $run->save();
                            $file->delete();
                            break;
                    }
                }
                if ($run->status == SubmitStatus::getDescription(SubmitStatus::WaitingInLine)) {
                    $job = ExecuteSubmitJob::dispatch($run)->delay(now()->addSeconds(5))->afterCommit();
                    if ($this->contestService->started && $this->contestService->inContest) {
                        $job->onQueue('contest');
                    }
                }
            }
            $run->save();
            NewSubmissionEvent::dispatch($run);

            return $run;
        });

        return redirect()->route('submission.index');
    }

    public function download(Submission $submission)
    {
        $this->authorize('view', $submission);
        if ($this->contestService->inContest && ! $this->contestService->contest->problems()->where('problems.id', $submission->problem_id)->exists()) {
            abort(404);
        }

        return $submission->file->download('#'.$submission->id.'_'.Str::slug($submission->problem->title).'.'.$submission->file->type);
    }

    public function getCode(Submission $submission)
    {
        $this->authorize('view', $submission);
        $code = $submission->file?->get() ?? 'Invalid Code!';
        if (! mb_check_encoding($code, 'UTF-8')) {
            $code = 'Malformed UTF-8 file!';
        }

        return response()->json([
            'code' => $code,
            'language' => match ($submission->language) {
                LanguagesType::name(LanguagesType::C) => 'c',
                LanguagesType::name(LanguagesType::CPlusPlus) => 'cpp',
                LanguagesType::name(LanguagesType::Java_OpenJDK24) => 'java',
                LanguagesType::name(LanguagesType::Python3_11) => 'python',
                LanguagesType::name(LanguagesType::Python3_13) => 'python',
                LanguagesType::name(LanguagesType::PyPy3_10) => 'python',
                LanguagesType::name(LanguagesType::PyPy3_11) => 'python',
                default => 'plaintext',
            },
        ]);
    }

    public function result(Submission $submission)
    {
        $this->authorize('view', $submission);

        return new SubmissionResultResource($submission);
    }

    public function rejudge(Submission $submission)
    {
        $this->authorize('update', $submission);
        /** @var User */
        $user = $this->user();
        if (RateLimiter::tooManyAttempts('resubmission:'.$user->id, 5)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Too many attempts! Wait a moment and try again!',
            ], 429);
        }
        // 10 minutes
        if (! $user->isAdmin()) {
            RateLimiter::hit('resubmission:'.$user->id, 60 * 10);
        }

        if (SubmitStatus::fromValue(SubmitStatus::Judged)->description == $submission->status || SubmitStatus::fromValue(SubmitStatus::Error)->description == $submission->status) {
            $submission->status = SubmitStatus::WaitingInLine;
            $submission->result = SubmitResult::NoResult;
            $submission->save();

            if ($submission->language == LanguagesType::name(LanguagesType::Auto_detect)) {
                $submission->language = LanguagesType::CPlusPlus;
            }
            ExecuteSubmitJob::dispatch($submission)->onQueue('low')->afterCommit();
        }

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function show(Submission $submission)
    {
        $this->authorize('viewOutput', $submission);

        return view('pages.run.show', [
            'submission' => $submission,
            'output' => $submission->output,
        ]);
    }
}
