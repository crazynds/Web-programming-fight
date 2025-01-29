<?php

namespace App\Services;

use App\Enums\LanguagesType;
use App\Enums\SubmitResult;
use App\Models\File;
use App\Models\Problem;
use App\Models\Scorer;
use App\Models\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ExecutorService
{

    public $output = null;
    public $retval = null;
    public $execution_time = 0;
    public $execution_memory = 0;

    private $currentConfig;
    private $diffConfig = null;

    public function __construct() {}

    private function cacheTime($fileSize)
    {
        if ($fileSize < 4 * 1024) {
            // less than 4kb
            // 60 minutes
            $time = 60 * 60;
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
            case "PyPy3.10":
                return '--conf /var/nsjail/python.conf -R /var/nsjail/runPypy3.10.sh --exec_file /var/nsjail/runPypy3.10.sh';
            case "Python3.11":
                return '--conf /var/nsjail/python.conf -R /var/nsjail/runPython3.11.sh --exec_file /var/nsjail/runPython3.11.sh';
            case "C++":
            case "C":
            case "BINARY":
            default:
                // USE C++
                return '--conf /var/nsjail/basic.conf -R /var/nsjail/runBinary.sh --exec_file /var/nsjail/runBinary.sh';
        }
    }

    private function loadFile($fileId, $path)
    {
        // Carrega o arquivo input para a pasta tmpfs
        $size = File::where('id', $fileId)->select('size')->first()->size;
        $time = $this->cacheTime($size);
        $fileData = Cache::remember('file:input_' . $fileId, $time, function () use ($fileId) {
            return File::find($fileId)->get();
        });
        Storage::disk('work')->put($path, $fileData);
        $fileData = null;   // free memory
    }

    public function setup(Problem $problem, File $code, string $language)
    {
        if ($problem->diff_program_language) {
            $result = $this->buildProgram($problem->diffProgram, $problem->diff_program_language, 'diff_exec');
            if ($result != SubmitResult::NoResult) {
                return SubmitResult::InternalCompilationError;
            }
            $this->diffConfig = $this->getConfig($problem->diff_program_language);
        }
        return $this->buildProgram($code, $language);
    }

    public function testOutputFile(TestCase $testCase)
    {
        $output_file = 'problems/output';
        $size = $testCase->outputfile()->select('size')->first()->size;
        $time = $this->cacheTime($size);
        $fileData = Cache::remember('file:output_' . $testCase->id, $time, function () use ($testCase) {
            return $testCase->outputfile->get();
        });
        Storage::disk('work')->put($output_file, $fileData);
        $fileData = null;   // free memory

        $foutput = '/var/work/' . $output_file;
        if (is_null($this->diffConfig)) {
            // a => compare text mode
            // b => ignore multiples blank lines (\n\r == \r\n == \n)
            // c => layout bonitinho
            // i => not case sensitive
            exec("diff -abci --suppress-common-lines --ignore-trailing-space /var/work/user_output $foutput > /var/work/diff", $this->output, $this->retval);
            if ($this->retval != 0) {
                $oldRetVal = $this->retval;
                $prepareOutput = "csplit /var/work/diff '/--- [0-9]*,*[0-9]* ----/' > /dev/null && sed -i '1i\\\\n\\n' xx01 && pr -mt -w 115 xx00 xx01 | head -n 20";
                exec($prepareOutput, $this->output, $this->retval);
                $this->retval = $oldRetVal;
                //dump($this->output);
            }
        } else {
            // Limit to 1 Mbytes
            $limitOutput = 1024 * 1024;
            $command = 'command time -v --output=/var/work/time -p nsjail ' . $this->diffConfig
                . ' --max_cpus 1 --log /var/work/nsjail_out --time_limit=4 --rlimit_as=1024'
                . ' -R /var/nsjail/runBinary.sh -R /var/work/problems -R /var/work/user_output'
                . ' diff /var/work/user_output /var/work/problems/output /var/work/problems/input'
                . ' 2>&1 | head -c ' . $limitOutput;



            exec("chmod 0644 /var/work/problems -R"); // Give access to problem input/output folder
            exec("chmod 0777 /var/work/problems"); // Give access to problem input/output folder
            exec("cp /var/work/diff_exec /var/nsjail/exec 2>&1 > /dev/null");   // Copy program to correct place
            exec("chmod +x /var/nsjail/exec 2>&1 > /dev/null");
            $this->output = [];
            exec($command, $this->output, $this->retval); // Execute
            foreach (explode(PHP_EOL, Storage::disk('work')->get('time')) as $line) {
                $arr = explode(': ', trim($line));
                switch ($arr[0]) {
                    case 'Exit status':
                        $this->retval = intval($arr[1]);
                        // Por algum motivo, a SBC padronizou que retorno 4 == valido e retorno 6 == invalido
                        if ($this->retval == 4) {
                            $this->retval = 0;
                        } else if ($this->retval == 6) {
                            $this->retval = 126;
                        }
                        break;
                    default:
                }
            }
            //dump($this->output, $this->retval);
        }
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

        // load output in input file
        $inputData .= Storage::disk('work')->get('output') . PHP_EOL;

        $oldConfig = $this->currentConfig;
        $this->buildProgram($scorer->file, $scorer->language, 'scorer');

        Storage::disk('work')->put('problems/input', $inputData);

        $modifiers = LanguagesType::modifiers()[$scorer->language];
        $timeLimit = $scorer->time_limit * $modifiers[0];
        $memoryLimit = $scorer->memory_limit * $modifiers[1];
        $this->execute($timeLimit, $memoryLimit, 'scorer');

        $output = Storage::disk('work')->get('output');

        // Restore old program config
        $this->currentConfig = $oldConfig;

        if ($this->retval != 0) return false;

        $output = explode(PHP_EOL, $output);
        $categories = [];
        foreach ($output as $line) {
            $arr = explode(' ', $line);
            if (sizeof($arr) != 3) continue;
            $category = str_replace('_', ' ', $arr[0]);
            if (empty($category)) continue;
            $categories[$category] = [
                'value' => floatval($arr[1]),
                'reference' => str_replace('_', ' ', $arr[2])
            ];
        }

        return $categories;
    }

    private function execute($timeLimit, $memoryLimit, $programName = 'program')
    {
        $finput = '/var/work/problems/input';

        // Limit to 536870912 bytes, so the file can't be bigger than 512 MB. (1024 * 1024 * 512)
        $limitOutput = 536870912;

        // Configure time limit and memory limit with a small margin
        $time_limit = round((1500 + ($timeLimit * 1.15)) / 1000);
        $memory_limit = $memoryLimit + 256;

        $command = 'command time -v --output=/var/work/time -p nsjail ' . $this->currentConfig . ' --max_cpus 1 --log /var/work/nsjail_out --time_limit=' . $time_limit . ' --rlimit_as=' . $memory_limit . ' < ' . $finput . ' 2> /dev/null | head -c ' . $limitOutput . ' > /var/work/user_output';

        exec("rm /var/work/nsjail_out 2> /dev/null");
        exec("cp /var/work/'$programName' /var/nsjail/exec 2>&1 > /dev/null");   // Copy program to correct place
        exec("chmod +x /var/nsjail/exec 2>&1 > /dev/null");
        exec($command, $this->output, $this->retval);


        //dump($this->output);
        $exectime = 0;
        $memoryPeak = 0;
        foreach (explode(PHP_EOL, Storage::disk('work')->get('time')) as $line) {
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
                        $nsjailOut = Storage::disk('work')->get('nsjail_out');
                        if (str_contains($nsjailOut, 'Killing it') && str_contains($nsjailOut, 'run time >= time limit')) {
                            $exectime = $time_limit * 1000;
                        }
                    }
                    break;
                default:
            }
        }
        if ($this->retval != 0) {
            $command = str_replace('command time -v --output=/var/work/time -p ', '', $command);
            $command  = str_replace('2> /dev/null', '2>&1', $command);
            exec($command);
            $this->output = Storage::disk('work')->get('user_output');
        }
        $this->execution_time = $exectime;
        $this->execution_memory = intval($memoryPeak);
        //dump($exectime, $memoryPeak, $retval);
        //dump('------');
    }


    private function buildProgram(File $code, $language, $outputName = 'program')
    {
        $this->currentConfig = $this->getConfig($language);
        switch ($language) {
            case "C++":
                $program = 'prog.cpp';
                Storage::disk('work')->writeStream($program, $code->readStream());
                exec("g++ -std=c++20 -mtune=native -Wreturn-type -static -march=native -w -O2 /var/work/'$program' -o /var/work/'$outputName' 2>&1", $this->output, $this->retval);
                if ($this->retval != 0) {
                    return SubmitResult::CompilationError;
                }
                break;
            case "C (-std=c17)":
                $program = 'prog.c';
                Storage::disk('work')->writeStream($program, $code->readStream());
                exec("gcc -std=c17 -mtune=native -lm -static -march=native -w -O2 /var/work/'$program' -o /var/work/'$outputName' 2>&1", $this->output, $this->retval);
                if ($this->retval != 0) {
                    return SubmitResult::CompilationError;
                }
                break;
            case "PyPy3.10":
            case "Python3.11":
                Storage::disk('work')->writeStream($outputName, $code->readStream());
                exec("sed -i '1s/^/from sys import exit\\n\\n/' /var/work/".$outputName); // Add import to exit
                break;
            case "BINARY":
                Storage::disk('work')->writeStream($outputName, $code->readStream());
                break;
            default:
                return SubmitResult::LanguageNotSupported;
        }
        return SubmitResult::NoResult;
    }
}
