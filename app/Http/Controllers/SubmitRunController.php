<?php

namespace App\Http\Controllers;

use App\Enums\LanguagesType;
use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Http\Requests\StoreSubmitRunRequest;
use App\Http\Resources\SubmitRunResultResource;
use App\Jobs\AutoDetectLangSubmitRun;
use App\Jobs\ExecuteSubmitJob;
use App\Models\Competitor;
use App\Models\Contest;
use App\Models\SubmitRun;
use App\Models\File;
use App\Models\Problem;
use App\Models\User;
use App\Services\ContestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class SubmitRunController extends Controller
{

    public function __construct(protected ContestService $contestService)
    {
        $this->authorizeResource(SubmitRun::class, 'submitRun');
    }

    public function global(Contest $contest = null)
    {
        return view('pages.run.index', [
            'global' => true,
            'contest' => $contest
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
        /** @var $user */
        $user = Auth::user();

        if ($this->contestService->inContest) {
            $problems = $this->contestService->contest->problems()->get();
        } else if ($user->isAdmin()) {
            $problems = Problem::all();
        } else {
            $problems = Problem::where('visible', true)->orWhere('user_id', $user->id)->get();
        }
        return view('pages.run.create', [
            'problems' => $problems,
            'selected' => $request->get('problem')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubmitRunRequest $request)
    {
        $user = Auth::user();
        if (RateLimiter::tooManyAttempts('submission:' . $user->id, 30)) {
            return Redirect::back()->withErrors(['msg' => 'Too many attempts! Wait a moment and try again!']);
        }
        $problem = Problem::findOrFail($request->problem);
        // check if problem exists in the contest
        if ($this->contestService->inContest && !$this->contestService->contest->problems()->where('problems.id', $problem->id)->exists()) {
            return Redirect::back()->withErrors(['msg' => 'Problem not found. Are you trying to cheat?']);
        }

        // 1 Hour
        RateLimiter::hit('submission:' . $user->id, 60 * 60);
        $run = DB::transaction(function () use ($request, $user, $problem) {

            $originalFile = $request->file('code');

            // 4 MB
            if ($originalFile->getSize() > 1024 * 1024 * 4 || !mb_check_encoding($originalFile->get(), 'UTF-8')) {
                $run = new SubmitRun();
                $run->language = $request->input('lang');
                $run->problem()->associate($problem);
                $run->user()->associate($user);
                $run->status = SubmitStatus::Judged;
                if ($originalFile->getSize() > 1024 * 1024 * 4)
                    $run->result = SubmitResult::FileTooLarge;
                else
                    $run->result = SubmitResult::InvalidUtf8File;
                $run->save();
            } else {
                $file = File::createFile($originalFile, 'users/' . $user->id . "/attempts" . '/' . $request->problem);

                $run = new SubmitRun();
                $run->language = $request->input('lang');
                $run->problem()->associate($problem);
                $run->file()->associate($file);
                $run->user()->associate($user);
                $run->status = SubmitStatus::WaitingInLine;
                $run->save();
                if ($run->language == LanguagesType::Auto_detect) {
                    $job = AutoDetectLangSubmitRun::dispatch($run)->afterCommit();
                } else $job = ExecuteSubmitJob::dispatch($run)->afterCommit();
            }
            if ($this->contestService->started && $this->contestService->inContest) {
                $run->contest()->associate($this->contestService->contest);
                $run->save();
                $this->contestService->competitor->submissions()->attach($run);
                if (isset($job)) $job->onQueue('contest');
            }
            return $run;
        });

        return redirect()->route('submitRun.index');
    }

    public function download(SubmitRun $submitRun)
    {
        $this->authorize('view', $submitRun);
        if ($this->contestService->inContest && !$this->contestService->contest->problems()->where('problems.id', $submitRun->problem_id)->exists()) {
            abort(404);
        }
        return $submitRun->file->download('#' . $submitRun->id . '_' . Str::slug($submitRun->problem->title) . '.' . $submitRun->file->type);
    }

    public function getCode(SubmitRun $submitRun)
    {
        $this->authorize('view', $submitRun);
        $code =  $submitRun->file?->get() ?? 'Invalid Code!';
        if (!mb_check_encoding($code, 'UTF-8'))
            $code = "Malformed UTF-8 file!";

        return response()->json([
            'code' => $code,
        ]);
    }

    public function result(SubmitRun $submitRun)
    {
        $this->authorize('view', $submitRun);
        return new SubmitRunResultResource($submitRun);
    }

    public function rejudge(SubmitRun $submitRun)
    {
        $this->authorize('update', $submitRun);
        /** @var User */
        $user = Auth::user();
        if (RateLimiter::tooManyAttempts('resubmission:' . $user->id, 5)) {
            return Redirect::back()->withErrors(['msg' => 'Too many attempts! Wait a moment and try again!']);
        }
        // 10 minutes
        if (!$user->isAdmin())
            RateLimiter::hit('resubmission:' . $user->id, 60 * 10);

        if (SubmitStatus::fromValue(SubmitStatus::Judged)->description == $submitRun->status || SubmitStatus::fromValue(SubmitStatus::Error)->description == $submitRun->status) {
            $submitRun->status = SubmitStatus::WaitingInLine;
            $submitRun->result = SubmitResult::NoResult;
            $submitRun->save();
            // Put this run in the low priority queue
            ExecuteSubmitJob::dispatch($submitRun)->onQueue('low')->afterCommit();
        }
        return redirect()->back();
    }

    public function show(SubmitRun $submitRun)
    {
        $this->authorize('viewOutput', $submitRun);
        return view('pages.run.show', [
            'submitRun' => $submitRun,
            'output' => $submitRun->output
        ]);
    }
}
