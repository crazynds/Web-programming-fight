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

    private function executeTestCase(TestCase $testCase){
        $root = '/var/work/';
        $dir = 'problems/'.$this->submit->problem->id;

        $input_file = $dir.'/in_'.$testCase->id;
        $output_file = $dir.'/out_'.$testCase->id;

        if(!Storage::disk('nsjail')->exists($input_file))
            Storage::disk('nsjail')->writeStream($input_file, $testCase->inputfile->readStream());
        if(!Storage::disk('nsjail')->exists($output_file))
            Storage::disk('nsjail')->writeStream($output_file, $testCase->outputfile->readStream());
        
        $finput = $root.$input_file;
        $foutput = $root.$output_file;

        // Limit to 134217728 chars, so the file can't be bigger than 128 MB. (1024 * 1024 * 128)
        $output = null;
        $retval = null;
        $time_limit = round((1500 + $this->submit->problem->time_limit)/1000);
        $memory_limit = $this->submit->problem->memory_limit + 256;
        $command = 'time -v --output=/var/work/time -p nsjail --conf /var/nsjail/basic.conf --time_limit='.$time_limit.' --rlimit_as='.$memory_limit.' < '.$finput.' 2>/dev/null | head -c 134217728 > /var/work/out_cpp';
        exec($command,$output,$retval);
        dump($command);

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
        dump($time,$memoryPeak,$retval);
        dump('------');
        // 9 MB is the margin to work
        if($memoryPeak>$this->submit->problem->memory_limit + 9){
            return SubmitResult::MemoryLimit;
        }
        if($time > $this->submit->problem->time_limit){
            return SubmitResult::TimeLimit;
        }
        // TODO: Memory limit ainda não tem como verificar
        if($retval!=0){
            return SubmitResult::RuntimeError;
        }else{
            // a => compare text mode
            // b => ignore multiples blank lines (\n\r == \r\n == \n)
            // c => layout bonitinho
            // i => não case sensitive
            exec('diff -abci --suppress-common-lines --ignore-trailing-space /var/work/out_cpp '.$foutput,$output,$retval);
            if($retval!=0){
                $this->submit->output = implode(PHP_EOL,$output);
                return SubmitResult::WrongAnswer;
            }
        }
        return SubmitResult::Accepted;
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
        $this->submit->save();
        switch($this->submit->language){
        case "C++":
            //$type = substr($file->type,0,3);
            //$program = '/work/prog.'.$type;
            $program = 'prog.cpp';
            Storage::disk('nsjail')->writeStream($program, $file->readStream());
            $output = null;
            $retval = null;
            exec("g++ -O2 /var/work/'$program' -o /var/nsjail/a.bin",$output,$retval);
            if($retval==0){
                $num = 0;

                $testCasesRel = [];
                $testCases = $this->submit->problem->testCases()->where('validated','=',true)->with(['inputfile','outputfile'])->get();


                foreach($testCases as $testCase){
                    $result = $this->executeTestCase($testCase);
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
                // Número de test cases que passou
                if($this->submit->result=='Accepted' || $testCases->count() == 0){

                    // Valida os casos de testes não validados até então...
                    foreach($this->submit->problem->testCases()->where('validated','=',false)->with(['inputfile','outputfile'])->get() as $testCase){
                        $result = $this->executeTestCase($testCase);
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
            }else{
                $this->submit->output = implode(PHP_EOL,$output);
                $this->submit->result = SubmitResult::CompilationError;
            }
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
