<?php

namespace App\Jobs;

use App\Enums\LanguagesType;
use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
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
use Throwable;

class ExecuteSubmitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SubmitRun $submit
    )
    {
        //
    }

    private function executeTestCase(TestCase $testCase,string $config){
        $input_file = 'problems/input';
        $output_file = 'problems/output';

        // Carrega o arquivo input para a pasta tmpfs

        $size = $testCase->inputfile()->select('size')->first()->size;
        if($size < 4*1024){
            // 60 minutes
            $time = 60 * 15;
        }else if($size < 1024 * 1024){
            // 15 minutes
            $time = 60 * 15;
        }else{
            // 5 minutes
            $time = 60 * 5;
        }
        $fileData = Cache::remember('input_'.$testCase->id, $time, function () use($testCase){
            return $testCase->inputfile->get();
        });
        Storage::disk('nsjail')->put($input_file, $fileData);
        $fileData = null;   // free memory

        $finput = '/var/work/'.$input_file;
        $foutput = '/var/work/'.$output_file;

        // Limit to 134217728 chars, so the file can't be bigger than 128 MB. (1024 * 1024 * 128)
        $limitOutput = 134217728;
        
        // Configure time limit and memory limit with a small margin
        $time_limit = round((1500 + $this->submit->problem->time_limit)/1000) ;
        $memory_limit = $this->submit->problem->memory_limit + 256;

        $command = 'time -v --output=/var/work/time -p nsjail --conf '.$config.' --time_limit='.$time_limit.' --rlimit_as='.$memory_limit.' < '.$finput.' 2>/dev/null | head -c '.$limitOutput.' > /var/work/out_cpp';

        $output = null;$retval = null;
        exec($command,$output,$retval);
        //dump($command);

        $time = 0;
        $memoryPeak = 0;
        foreach(explode(PHP_EOL,Storage::disk('nsjail')->get('time')) as $line){
            $arr = explode(': ',trim($line));
            switch($arr[0]){
                case 'User time (seconds)':
                    $time = intval(floatval($arr[1])*1000);
                    break;
                case 'Maximum resident set size (kbytes)':
                    $memoryPeak = floatval($arr[1])/1024;
                    break;
                case 'Exit status':
                    $retval = intval($arr[1]);
                    break;
                default:
            }
        }
        $this->submit->execution_time = max($this->submit->execution_time|0,$time);
        $this->submit->execution_memory = max($this->submit->execution_memory|0,intval($memoryPeak));
        dump($time,$this->submit->execution_memory,$retval);-
        dump('------');
        // 9 MB is the margin to work
        if($memoryPeak>$this->submit->problem->memory_limit + 9){
            return SubmitResult::MemoryLimit;
        }
        if($time > $this->submit->problem->time_limit){
            return SubmitResult::TimeLimit;
        }
        if($retval!=0){
            // TODO: Um RuntimeError pode ser causado por memory limit, mas não tem como saber nesses casos
            // Buscar solução alternativa
            return SubmitResult::RuntimeError;
        }else{
            // Carrega o arquivo output para a pasta tmpfs
            $size = $testCase->outputfile()->select('size')->first()->size;
            if($size < 4*1024){
                // 60 minutes
                $time = 60 * 15;
            }else if($size < 1024 * 1024){
                // 15 minutes
                $time = 60 * 15;
            }else{
                // 5 minutes
                $time = 60 * 5;
            }
            $fileData = Cache::remember('output_'.$testCase->id, $time, function () use($testCase){
                return $testCase->outputfile->get();
            });
            Storage::disk('nsjail')->put($output_file, $fileData);
            $fileData = null;   // free memory

            // a => compare text mode
            // b => ignore multiples blank lines (\n\r == \r\n == \n)
            // c => layout bonitinho
            // i => not case sensitive
            exec('diff -abci --suppress-common-lines --ignore-trailing-space /var/work/out_cpp '.$foutput,$output,$retval);
            if($retval!=0){
                $this->submit->output = implode(PHP_EOL,$output);
                return SubmitResult::WrongAnswer;
            }
        }
        return SubmitResult::Accepted;
    }

    private function executeAllTestCases(string $config){
        $num = 0;
        $testCasesRel = [];
        $testCases = $this->submit->problem->testCases()->where('validated','=',true)->with(['inputfile','outputfile'])->get();

        foreach($testCases as $testCase){
            $result = $this->executeTestCase($testCase,$config);
            $testCasesRel[$testCase->id] = [
                'result' => $result
            ];
            $this->submit->result = $result;
            if($result == SubmitResult::Accepted){
                $num += 1;
            }else{
                break;
            }
        }
        if($this->submit->result=='Accepted' || $testCases->count() == 0){

            // Tenta validar os casos de testes não validados até então...
            foreach($this->submit->problem->testCases()->where('validated','=',false)->with(['inputfile','outputfile'])->get() as $testCase){
                $result = $this->executeTestCase($testCase,$config);
                $testCasesRel[$testCase->id] = [
                    'result' => $result
                ];
                if($result == SubmitResult::Accepted){
                    $num += 1;
                    $testCase->validated = true;
                    $testCase->save();
                }
            }
            if($num > 0)
                $this->submit->result = SubmitResult::Accepted;
            else
                $this->submit->result = SubmitResult::WrongAnswer;
            $this->submit->output = null;
        }
        $this->submit->testCases()->sync($testCasesRel);
        $this->submit->num_test_cases = $num;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $file = $this->submit->file;
        $this->submit->status = SubmitStatus::Judging;
        $this->submit->result = SubmitResult::NoResult;
        $this->submit->output = null;
        $this->submit->execution_memory = null;
        $this->submit->execution_time = null;
        $this->submit->save();
        $confFile = '/var/nsjail/basic.conf';
        switch($this->submit->language){
        case "C++":
            //$type = substr($file->type,0,3);
            //$program = '/work/prog.'.$type;
            $program = 'prog.cpp';
            Storage::disk('nsjail')->writeStream($program, $file->readStream());
            $output = null;
            $retval = null;
            exec("g++ -std=c++20 -mtune=native -match=native -w -O2 /var/work/'$program' -o /var/nsjail/a.bin 2>&1",$output,$retval);
            if($retval!=0){
                $this->submit->output = implode(PHP_EOL,$output);
                $this->submit->result = SubmitResult::CompilationError;
            }else
                $this->executeAllTestCases('/var/nsjail/basic.conf');
            break;
        case "Python":
            $this->submit->result = SubmitResult::Error;
            break;
        default:        
            $this->submit->result = SubmitResult::Error;
        }
        $this->submit->status = SubmitStatus::Judged;
        $this->submit->save();
    }

    public function failed(Throwable $exception): void
    {
        $this->submit->status = SubmitStatus::Error;
        $this->submit->result = SubmitResult::Error;
        $this->submit->output = $exception->__toString();
        $this->submit->save();
    }
}
