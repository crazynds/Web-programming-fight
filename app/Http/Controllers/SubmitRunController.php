<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubmitRunRequest;
use App\Models\SubmitRun;
use App\Models\File;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitRunController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return SubmitRun::all();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.submit');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubmitRunRequest $request)
    {
        DB::transaction(function() use($request){
            $user = User::query()->firstOrCreate([
                'name' => "test",
                'email' => "test@test.com",
                'password' => 'test@test',
            ]);
            $originalFile = $request->file('code');

            $file = new File();
            $file->path = $originalFile->store('attempts/code');
            $file->type = $originalFile->getType();
            $file->size = $originalFile->getSize();
            $file->type = $originalFile->getClientOriginalExtension();
            $file->hash = hash_file("sha256",$originalFile->getPathname());
            $file->save();

            $run = new SubmitRun();
            $run->language = $request->input('lang');
            $run->file()->associate($file);
            $run->user()->associate($user);
            $run->save();
        });
        return redirect()->route('run.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(SubmitRun $submitRun)
    {
        //
    }

}
