<?php

namespace App\Http\Controllers;

use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Http\Requests\StoreSubmitRunRequest;
use App\Jobs\ExecuteSubmitJob;
use App\Models\SubmitRun;
use App\Models\File;
use App\Models\Problem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;

class SubmitRunController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(SubmitRun::class, 'submitRun');
    }

    public function global()
    {
        return view('pages.run.index',[
            'submitRuns' => SubmitRun::orderByDesc('id')->limit(100)->get()
        ]);
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.run.index',[
            'submitRuns' => $this->user()->submissions()->orderBy('id','desc')->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.run.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubmitRunRequest $request)
    {
        $user = Auth::user();
        if (RateLimiter::tooManyAttempts('submission:'.$user->id, $perMinute = 4)) {
            return Redirect::back()->withErrors(['msg' => 'Too many attempts! Wait a moment and try again!']);
        }
        DB::transaction(function() use($request){
            $user = User::first();
            if(!$user)
                $user = User::query()->create([
                    'name' => "test",
                    'email' => "test@test.com",
                    'password' => 'test@test',
                ]);
            $originalFile = $request->file('code');

            // 16 MB
            if($originalFile->getSize()>1024*1024*16){
                $run = new SubmitRun();
                $run->language = $request->input('lang');
                $run->problem()->associate(Problem::find($request->problem));
                $run->user()->associate($user);
                $run->status = SubmitStatus::Judged;
                $run->result = SubmitResult::FileTooLarge;
                $run->save();
            }else{
                $file = new File();
                $file->path = $originalFile->store('attempts/code');
                $file->type = $originalFile->getType();
                $file->size = $originalFile->getSize();
                $file->type = $originalFile->getClientOriginalExtension();
                $file->hash = hash_file("sha256",$originalFile->getPathname());
    
                
    
                $file->save();
    
                $run = new SubmitRun();
                $run->language = $request->input('lang');
                $run->problem()->associate(Problem::find($request->problem));
                $run->file()->associate($file);
                $run->user()->associate($user);
                $run->status = SubmitStatus::WaitingInLine;
                $run->save();
    
                ExecuteSubmitJob::dispatch($run)->onQueue('submit')->afterCommit();
            }
        });
        return redirect()->route('run.index');
    }

    public function rejudge(SubmitRun $submitRun)
    {
        $submitRun->status = SubmitStatus::WaitingInLine;
        $submitRun->result = SubmitResult::NoResult;
        $submitRun->save();
        ExecuteSubmitJob::dispatch($submitRun)->onQueue('submit')->afterCommit();
        return redirect()->route('run.index');
    }

    public function show(SubmitRun $submitRun)
    {
        return view('pages.run.show',[
            'submitRun' => $submitRun,
            'output' => $submitRun->output
        ]);
    }

}
