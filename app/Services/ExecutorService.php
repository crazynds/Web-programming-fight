<?php

namespace App\Services;

use App\Enums\LanguagesType;
use App\Enums\SubmitResult;
use App\Models\File;
use App\Models\Scorer;
use App\Models\SubmitRun;
use App\Models\TestCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ExecutorService
{

    public $output = null;
    public $retval = null;
    public $execution_time = 0;
    public $execution_memory = 0;

    private $currentConfig;

    public function __construct()
    {
    }

    private function cacheTime($fileSize)
    {
        if ($fileSize < 4 * 1024) {
            // less than 4kb
            // 60 minutes
            $time = 60 * 15;
        } else if ($fileSize < 1024 * 1024) {
            // less than 1 MB
            // 15 minutes
            $time = 60 * 15;
        } else {
            // 5 minutes
            $time = 60 * 5;
        }
        return $time;
    }


    private function getConfig($language)
    {
        switch ($language) {
            case "C++":
                return '--conf /var/nsjail/basic.conf';
            case "PyPy3.10":
                return '--user 99999 --group 99999 -Mo -R /bin/ -R /lib -R /lib64/ -R /usr/ -R /sbin/ -T /dev -R /dev/urandom -R /var/nsjail/runPypy3.10.sh -R /var/nsjail/b.py --exec_file /var/nsjail/runPypy3.10.sh';
            case "Python3.11":
                return '--user 99999 --group 99999 -Mo -R /bin/ -R /lib -R /lib64/ -R /usr/ -R /sbin/ -T /dev -R /dev/urandom -R /var/nsjail/runPython3.11.sh -R /var/nsjail/b.py --exec_file /var/nsjail/runPython3.11.sh';
            default:
                // USE C++
                return '--conf /var/nsjail/basic.conf';
        }
    }

    public function testOutputFile(TestCase $testCase)
    {
        $output_file = 'problems/output';
        $size = $testCase->outputfile()->select('size')->first()->size;
        $time = $this->cacheTime($size);
        $fileData = Cache::remember('file:output_' . $testCase->id, $time, function () use ($testCase) {
            return $testCase->outputfile->get();
        });
        Storage::disk('nsjail')->put($output_file, $fileData);
        $fileData = null;   // free memory

        $foutput = '/var/work/' . $output_file;

        // a => compare text mode
        // b => ignore multiples blank lines (\n\r == \r\n == \n)
        // c => layout bonitinho
        // i => not case sensitive
        exec('diff -abci --suppress-common-lines --ignore-trailing-space /var/work/output ' . $foutput, $this->output, $this->retval);

        //dump($this->output);
    }

    public function loadFile($fileId, $path)
    {
        // Carrega o arquivo input para a pasta tmpfs
        $size = File::where('id', $fileId)->select('size')->first()->size;
        $time = $this->cacheTime($size);
        $fileData = Cache::remember('file_' . $fileId, $time, function () use ($fileId) {
            return File::find($fileId)->get();
        });
        Storage::disk('nsjail')->put($path, $fileData);
        $fileData = null;   // free memory
    }

    public function execute($timeLimit, $memoryLimit)
    {
        $finput = '/var/work/problems/input';

        // Limit to 134217728 chars, so the file can't be bigger than 128 MB. (1024 * 1024 * 128)
        $limitOutput = 134217728;

        // Configure time limit and memory limit with a small margin
        $time_limit = round((1500 + $timeLimit) / 1000);
        $memory_limit = $memoryLimit + 256;

        $command = 'command time -v --output=/var/work/time -p nsjail ' . $this->currentConfig . ' --max_cpus 1 --log /var/work/nsjail_out --time_limit=' . $time_limit . ' --rlimit_as=' . $memory_limit . ' < ' . $finput . ' 2> /dev/null | head -c ' . $limitOutput . ' > /var/work/output';

        dump($command);
        exec($command, $this->output, $this->retval);


        //dump($this->output);
        $exectime = 0;
        $memoryPeak = 0;
        foreach (explode(PHP_EOL, Storage::disk('nsjail')->get('time')) as $line) {
            $arr = explode(': ', trim($line));
            switch ($arr[0]) {
                case 'User time (seconds)':
                    $exectime = intval(floatval($arr[1]) * 1000);
                    break;
                case 'Maximum resident set size (kbytes)':
                    // Subtract 6 MB because of the nsjail overhead
                    $memoryPeak = floatval($arr[1]) / 1024 - 6;
                    break;
                case 'Exit status':
                    $this->retval = intval($arr[1]);
                    if ($this->retval != 0) {
                        $nsjailOut = Storage::disk('nsjail')->get('nsjail_out');
                        if (str_contains($nsjailOut, 'Killing it')) {
                            if (str_contains($nsjailOut, 'run time >= time limit')) {
                                $exectime = $timeLimit + 1500;
                            }
                        }
                    }
                    break;
                default:
            }
        }
        $this->execution_time = $exectime;
        $this->execution_memory = intval($memoryPeak);
        //dump($exectime, $memoryPeak, $retval);
        //dump('------');
    }

    public function executeTestCase(TestCase $testCase, $timeLimit, $memoryLimit)
    {
        $input_file = 'problems/input';

        // Carrega o arquivo input para a pasta tmpfs
        dump('Testcase: ' . $testCase->name);
        $this->loadFile($testCase->input_file, $input_file);

        $this->execute($timeLimit, $memoryLimit);
    }

    public function executeScorer(Scorer $scorer)
    {
        // prepare input file
        $inputData = sprintf("%d %d\n", $this->execution_memory, $this->execution_time);
        // load input file in inputData??
        //$inputData .= Storage::disk('nsjail')->get('');

        // load outdata in fileData??
        $inputData .= Storage::disk('nsjail')->get('output') . PHP_EOL;

        $bkp = 'bkp';
        switch ($scorer->language) {
            case "C++":
            default:
                $program = 'a.bin';
                break;
            case "Python3.11":
            case "Pypy3.10":
                $program = 'b.py';
                break;
        }
        // Delete old backup
        exec("rm /var/nsjail/'$bkp'", $this->output, $this->retval);

        // Backup old program
        exec("mv /var/nsjail/'$program' /var/nsjail/'$bkp'", $this->output, $this->retval);
        $oldConfig = $this->currentConfig;
        $this->buildProgram($scorer->file, $scorer->language);

        Storage::disk('nsjail')->put('problems/input', $inputData);

        $modifiers = LanguagesType::modifiers()[$scorer->language];
        $timeLimit = $scorer->time_limit * $modifiers[0];
        $memoryLimit = $scorer->time_limit * $modifiers[1];
        $this->execute($timeLimit, $memoryLimit);

        $output = Storage::disk('nsjail')->get('output');

        // Restore old program
        $this->currentConfig = $oldConfig;
        exec("mv /var/nsjail/'$bkp' /var/nsjail/'$program'", $this->output, $this->retval);

        if ($this->retval != 0) return false;

        $output = explode(PHP_EOL, $output);
        $categories = [];
        foreach ($output as $line) {
            $arr = explode(' ', $line);
            $category = implode(' ', array_slice($arr, 0, -1));
            if (empty($category)) continue;
            $categories[$category] = floatval($arr[count($arr) - 1]);
        }

        return $categories;
    }

    public function buildProgram(File $code, $language)
    {
        $this->currentConfig = $this->getConfig($language);
        switch ($language) {
            case "C++":
                $outputName = 'a.bin';
                $program = 'prog.cpp';
                Storage::disk('nsjail')->writeStream($program, $code->readStream());
                exec("g++ -std=c++20 -mtune=native -march=native -w -O2 /var/work/'$program' -o /var/nsjail/'$outputName' 2>&1", $this->output, $this->retval);
                if ($this->retval != 0) {
                    return SubmitResult::CompilationError;
                }
                break;
            case "PyPy3.10":
            case "Python3.11":
                $outputName = 'b.py';
                Storage::disk('nsjail')->writeStream($outputName, $code->readStream());
                exec('mv /var/work/' . $outputName . ' /var/nsjail/' . $outputName, $this->output, $this->retval);
                break;
            default:
                return SubmitResult::Error;
        }
        return SubmitResult::NoResult;
    }
}
