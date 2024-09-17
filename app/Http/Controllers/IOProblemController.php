<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadProblemRequest;
use App\Models\File;
use App\Models\Problem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Zip;
use ZipArchive;

class IOProblemController extends Controller
{

    public function import()
    {
        Gate::authorize('import-zip');
        return view('pages.problem.import');
    }

    public function importSbc()
    {
        Gate::authorize('import-zip');
        return view('pages.problem.importSbc');
    }

    public function uploadSbc(UploadProblemRequest $request) {}

    public function upload(UploadProblemRequest $request)
    {

        /** @var UploadedFile $file */
        $file = $request->file('file');

        DB::beginTransaction();

        $zp = new ZipArchive();

        $zp->open($file->path(), ZipArchive::RDONLY);

        $files = [];

        for ($i = 0; $i < $zp->numFiles; $i++) {
            $file = $zp->statIndex($i);
            $files[$file['name']] = $file;
        }
        if (!isset($files['testCases.json']) || !isset($files['config.json'])) {
            return redirect()->back()->withErrors(['msg' => 'Invalid File.']);
        }

        $config = json_decode($zp->getFromName('config.json'), true);
        $testCases = json_decode($zp->getFromName('testCases.json'), true);

        /** @var Problem $problem */
        $problem = Problem::create([
            ...$config,
            'user_id' => Auth::user()->id
        ]);

        $hashStream = function ($fp) {
            $ctx = hash_init('sha256');
            hash_update_stream($ctx, $fp);
            fclose($fp);
            return hash_final($ctx);
        };

        foreach ($testCases as $testCase) {
            $inFile = $files['input/' . $testCase['name']];
            if (!$inFile)
                return redirect()->back()->withErrors(['msg' => 'Invalid File.']);
            $stream = $zp->getStream($inFile['name']);
            $hash = $hashStream($stream);
            $stream = $zp->getStream($inFile['name']);
            $inputFile = File::createFileByStream($stream, $inFile['size'], $hash, "problems/{$problem->id}/input");

            $outFile = $files['output/' . $testCase['name']];
            if (!$outFile)
                return redirect()->back()->withErrors(['msg' => 'Invalid File.']);
            $stream = $zp->getStream($outFile['name']);
            $hash = $hashStream($stream);
            $stream = $zp->getStream($outFile['name']);
            $outputFile = File::createFileByStream($stream, $outFile['size'], $hash, "problems/{$problem->id}/output");

            $problem->testCases()->updateOrCreate([
                'name' => $testCase['name'],
            ], [
                'problem_id' => $problem->id,
                'position' => $testCase['position'],
                'type' => $testCase['type'],
                'public' => $testCase['public'],
                'input_file' => $inputFile->id,
                'output_file' => $outputFile->id,
            ]);
        }
        $zp->close();
        DB::commit();

        return redirect()->route('problem.show', ['problem' => $problem->id]);
    }

    public function download(Problem $problem)
    {
        $this->authorize('update', $problem);
        $zipFileName = 'problem_' . $problem->id . '.zip';

        $zip = Zip::create($zipFileName);
        $titulo = $problem->title;
        $description = $problem->description;
        $input_description = $problem->input_description;
        $output_description = $problem->output_description;

        $markdown = "# {$titulo}

{$description}

## Input

{$input_description}

## Output

{$output_description}";
        $p = Problem::select([
            'title',
            'author',
            'time_limit',
            'memory_limit',
            'description',
            'input_description',
            'output_description',
        ])->find($problem->id);
        $zip->addRaw($markdown, 'README.md')
            ->addRaw(json_encode($p->toArray()), 'config.json');

        foreach ($problem->testCases()->with(['inputFile', 'outputFile'])->lazy() as $testCase) {
            /** @var File $inFile */
            $inFile = $testCase->inputFile;
            /** @var File $outFile */
            $outFile = $testCase->outputFile;

            $inFile->addToZip($zip, 'input/' . $testCase->name);
            $outFile->addToZip($zip, 'output/' . $testCase->name);
        }
        $testCases = $problem->testCases()->select([
            'position',
            'type',
            'public',
            'name'
        ])->get()->each->toArray();
        $zip->addRaw(json_encode($testCases->toArray()), 'testCases.json');



        foreach ($problem->scores()->with(['input', 'file'])->lazy() as $scorer) {
            $file = $scorer->file;
            $input = $scorer->input;

            $folderName = 'scores/' . $scorer->name . '#' . $scorer->id;

            $file->addToZip($zip, $folderName . '/code');
            $input->addToZip($zip, $folderName . '/input');
        }

        return $zip;
    }
}
