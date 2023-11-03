<?php

namespace App\Http\Controllers;

use App\Enums\TestCaseType;
use App\Http\Requests\StoreTestCaseRequest;
use App\Http\Requests\UpdateTestCaseRequest;
use App\Models\File;
use App\Models\Problem;
use App\Models\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class TestCaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Problem $problem)
    {
        return view('pages.testCase.index',[
            'problem' => $problem,
            'testCases' => $problem->testCases
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Problem $problem)
    {
        return view('pages.testCase.create',[
            'problem' => $problem,
            'testCase' => new TestCase(),
        ]);
    }
    
    public function downloadInput(Request $request, Problem $problem,TestCase $testCase)
    {
        dd($problem,$testCase);
        return Storage::download($testCase->inputfile->path);
    }
    
    public function downloadOutput(Problem $problem,TestCase $testCase)
    {
        return Storage::download($testCase->outputfile->path);
    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTestCaseRequest $request,Problem $problem)
    {
        $inputs = [];
        foreach($request->file('inputs') as $file){
            $inputs[$file->getClientOriginalName()] = $file;
        }
        $outputs = [];
        foreach($request->file('outputs') as $file){
            $outputs[$file->getClientOriginalName()] = $file;
        }
        $files = array_intersect(array_keys($inputs),array_keys($outputs));
        DB::transaction(function() use($problem,$inputs,$outputs,$files){
            $position = $problem->testCases()->count();
            foreach($files as $file){
                $position++;
                
                $inputFile = new File();
                $inputFile->path = $inputs[$file]->store("problems/{$problem->id}/input");
                $inputFile->type = $inputs[$file]->getType();
                $inputFile->size = $inputs[$file]->getSize();
                $inputFile->type = $inputs[$file]->getClientOriginalExtension();
                $inputFile->hash = hash_file("sha256",$inputs[$file]->getPathname());
                $inputFile->save();
                
                $outputFile = new File();
                $outputFile->path = $outputs[$file]->store("problems/{$problem->id}/output");
                $outputFile->type = $outputs[$file]->getType();
                $outputFile->size = $outputs[$file]->getSize();
                $outputFile->type = $outputs[$file]->getClientOriginalExtension();
                $outputFile->hash = hash_file("sha256",$outputs[$file]->getPathname());
                $outputFile->save();

                $testCase = new TestCase();
                $testCase->position = $position;
                $testCase->type = TestCaseType::FileDiff;
                $testCase->inputfile()->associate($inputFile);
                $testCase->outputfile()->associate($outputFile);
                $testCase->problem()->associate($problem);
                $testCase->rankeable = false;
                $testCase->save();
            }
        });
        return redirect()->route('problem.testCase.index',['problem'=>$problem->id]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Problem $problem,$testCase)
    {
        $testCase = $problem->testCases()->where('id','=',$testCase)->first();
        $testCase = TestCase::find($testCase->id);
        if(!$testCase)
            return redirect()->route('problem.testCase.index',['problem'=>$problem->id]);

        $testCase->delete();
        $problem->testCases()
            ->where('position','>',$testCase->position)
            ->decrement('position');
        return redirect()->route('problem.testCase.index',['problem' => $problem->id]);
    }
}
