<?php

namespace App\Jobs;

use App\Enums\LanguagesType;
use App\Enums\TagTypeEnum;
use App\Enums\TestCaseType;
use App\Models\File;
use App\Models\Problem;
use App\Models\Tag;
use App\Models\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PharData;
use Spatie\PdfToText\Pdf;
use ZipArchive;

class PrepareSBCProblemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected File $zip, protected User $user, protected string $eventName)
    {
        $this->onQueue('high');
    }

    public function adicionaProblema(string $path)
    {
        // Extrair arquivo
        $zp = new ZipArchive;
        $zp->open('/var/work/'.$path, ZipArchive::RDONLY);
        $problemInfo = $zp->getFromName('description/problem.info');
        $problemInfo = explode("\n", $problemInfo);
        $problemInfo = array_column(array_map(fn ($x) => explode('=', $x), $problemInfo), 1, 0);
        // Pegar informações de titulo/descrição/input/output
        $title = trim(str_replace('"', '', $problemInfo['fullname']));
        $letter = $problemInfo['basename'];
        $data = $zp->getFromName('description/'.$problemInfo['descfile']);
        if (str_ends_with($problemInfo['descfile'], '.pdf')) {
            Storage::disk('work')->put('sbc.pdf', $data);
            $text = Pdf::getText('/var/work/sbc.pdf', options: [
                'x 0',
                'y 50',
                'H 10000',
                'W 10000',
            ]);
            $text = explode(PHP_EOL, $text, 4)[3];
            $text = explode('Entrada'.PHP_EOL, $text, 2);
            $descricao = $text[0];

            $tmp = explode('Saı́da'.PHP_EOL, $text[1], 2);
            if (count($tmp) == 1) {
                $tmp = explode('Saída'.PHP_EOL, $text[1], 2);
            }
            $text = $tmp;

            $input = $text[0];
            $text = explode('Exemplo de entrada 1'.PHP_EOL, $text[1], 2);
            $output = $text[0];
        } else {
            // Significa que não é um pdf e não sei oq fazer com isso.
            return;
        }
        // Pegar os time limits
        $limit = $zp->getFromName('limits/cpp');
        preg_match_all('/echo (\d+)/', $limit, $matches);
        $matches = $matches[1];
        $timelimit = (int) ((intval($matches[0]) * 1000) / intval($matches[1]));
        $memorylimit = $matches[2];
        /** @var Problem */
        $problem = Problem::create([
            'title' => $title,
            'description' => $descricao,
            'input_description' => $input,
            'output_description' => $output,
            'user_id' => $this->user->id,
            'time_limit' => $timelimit,
            'memory_limit' => $memorylimit,
        ]);

        $testCaseCount = 0;
        $publicTestCases = 2;

        for ($i = 0; $i < $zp->numFiles; $i++) {
            $filename = $zp->getNameIndex($i);

            if (str_starts_with($filename, 'input/') && ! str_ends_with($filename, '/')) {
                $baseFilename = basename($filename);
                $outputFilename = 'output/'.$baseFilename;

                if (($inputContent = $zp->getFromName($filename)) !== false &&
                    ($outputContent = $zp->getFromName($outputFilename)) !== false) {

                    $inputFile = File::createFileByData($inputContent, "problems/{$problem->id}/input");
                    $outputFile = File::createFileByData($outputContent, "problems/{$problem->id}/output");

                    $problem->testCases()->create([
                        'name' => $baseFilename,
                        'type' => TestCaseType::FileDiff,
                        'input_file' => $inputFile->id,
                        'output_file' => $outputFile->id,
                        'validated' => true,
                        'public' => $testCaseCount < $publicTestCases,
                        'position' => $problem->testCases()->count() + 1,
                    ]);

                    $testCaseCount++;
                }
            }
        }
        // Vincular as tags
        $problem->tags()->attach(Tag::firstOrCreate(
            ['name' => 'Imported'],
            ['type' => TagTypeEnum::Others]
        ));
        $problem->tags()->attach(Tag::firstOrCreate(
            ['name' => 'Brasil'],
            ['type' => TagTypeEnum::Local]
        ));
        $problem->tags()->attach(Tag::firstOrCreate(
            ['name' => 'SBC'],
            ['type' => TagTypeEnum::Local]
        ));
        $problem->tags()->attach(Tag::firstOrCreate(
            ['name' => $this->eventName],
            ['type' => TagTypeEnum::Event]
        ));

        // Pegar programa comparador
        if ($data = $zp->getFromName('compare/cpp')) {
            $file = File::createFileByData($data, "problems/{$problem->id}/diff", preventCompact: true);
            $problem->diffProgram()->associate($file);
            $problem->diff_program_language = LanguagesType::BINARY;
            $problem->save();
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Storage::disk('work')->put('sbc.tar', $this->zip->readStream());
        $phar = new PharData('/var/work/sbc.tar');
        exec('rm -rf /var/work/sbc'); // Force to delete old dir
        $phar->extractTo('/var/work/sbc', null, true); // Force extract all files
        foreach (Storage::disk('work')->allFiles('sbc') as $file) {
            if (str_ends_with($file, '.zip')) {
                try {
                    DB::transaction(function () use ($file) {
                        $this->adicionaProblema($file);
                    });
                } catch (Exception $e) {
                    throw $e;
                }
            }
        }

        exec('rm -rf /var/work/sbc');
        $this->zip->delete();
    }

    public function failed()
    {
        $this->zip->delete();
        exec('rm -rf /var/work/sbc');
    }
}
