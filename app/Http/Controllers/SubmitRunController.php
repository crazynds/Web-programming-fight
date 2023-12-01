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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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
        if (RateLimiter::tooManyAttempts('submission:'.$user->id, 30)) {
            return Redirect::back()->withErrors(['msg' => 'Too many attempts! Wait a moment and try again!']);
        }
        // 1 Hour
        RateLimiter::hit('submission:'.$user->id, 60*60);
        DB::transaction(function() use($request,$user){
            
            $originalFile = $request->file('code');

            // 4 MB
            if($originalFile->getSize()>1024*1024*4){
                $run = new SubmitRun();
                $run->language = $request->input('lang');
                $run->problem()->associate(Problem::find($request->problem));
                $run->user()->associate($user);
                $run->status = SubmitStatus::Judged;
                $run->result = SubmitResult::FileTooLarge;
                $run->save();
            }else{
                $file = File::createFile($originalFile,'users/'.$user->id."/attempts".'/'.$request->problem);    
    
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
        return redirect()->route('submitRun.index');
    }
    
    public function download(SubmitRun $submitRun)
    {
        return $submitRun->file->download('#'.$submitRun->id.'_'.Str::slug($submitRun->problem->title).'.'.$submitRun->file->type);
    }

    public function getCode(SubmitRun $submitRun){
        return response()->json([
            'code' => $submitRun->file->get()
        ]);
    }

    public function rejudge(SubmitRun $submitRun)
    {
        /** @var User */
        $user = Auth::user();
        if (RateLimiter::tooManyAttempts('resubmission:'.$user->id, 5)) {
            return Redirect::back()->withErrors(['msg' => 'Too many attempts! Wait a moment and try again!']);
        }
        // 10 minutes
        if(!$user->isAdmin())
            RateLimiter::hit('resubmission:'.$user->id,60*10);
        
        if(SubmitResult::fromValue(SubmitResult::NoResult)->description != $submitRun->result){
            $submitRun->status = SubmitStatus::WaitingInLine;
            $submitRun->result = SubmitResult::NoResult;
            $submitRun->save();
            ExecuteSubmitJob::dispatch($submitRun)->onQueue('submit')->afterCommit();
        }
        return redirect()->back();
    }

    public function show(SubmitRun $submitRun)
    {
        return view('pages.run.show',[
            'submitRun' => $submitRun,
            'output' => $submitRun->output
        ]);
    }

}
