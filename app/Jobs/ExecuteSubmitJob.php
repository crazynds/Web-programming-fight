<?php

namespace App\Jobs;

use App\Enums\LanguagesType;
use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Models\SubmitRun;
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

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        // command
        /**
         *   nsjail -Ml --port 3001 
         *      --user 99999 
         *      --group 99999 
         *      --disable_proc 
         *      --chroot /task 
         *      --time_limit 15 
         *      -R /lib/ 
         *      -R /lib64/ 
         *      -R /usr/bin/ 
         *      /task_exec
         *  
         *  nsjail -Mo --user 99999 --group 99999 --disable_proc -R /lib64/ -R /lib/ --time_limit 15 --max_cpus 2 --disable_clone_newuser /task/run.bin
         */

        $file = $this->submit->file;
        $this->submit->status = SubmitStatus::Judging;
        $this->submit->result = SubmitResult::NoResult;
        $this->submit->save();

        switch($this->submit->language){
            case LanguagesType::CPlusPlus:
                //$type = substr($file->type,0,3);
                //$program = '/work/prog.'.$type;
                $program = '/var/work/prog.cpp';
                $output = '/var/work/prog.cpp';
                file_put_contents($program,Storage::get($file->path));    
                $output = null;
                $retval = null;
                exec("g++ -O2 '$program' -o /var/work/a.bin",$output,$retval);
                if($retval==0){
                    $output = null;
                    $retval = null;
                    
                    // TODO: aqui deve ser feito os testes para os casos de accept.
                    // Limit to 134217728 chars, so the file can't be bigger than 128 MB. (1024 * 1024 * 128)
                    exec('nsjail --conf /var/nsjail/basic.conf | head -c 134217728 > /var/work/out_cpp',$output,$retval);
                    if($retval!=0){
                        $this->submit->result = SubmitResult::RuntimeError;
                    }else{
                        dump($output);
                        dump($retval);
                        $this->submit->result = SubmitResult::Accepted;
                    }
                }else{
                    $this->submit->result = SubmitResult::CompilationError;
                }
                break;
            case LanguagesType::Python:
                break;
        }
        $this->submit->status = SubmitStatus::Judged;
        $this->submit->save();
    }

    public function failed(Throwable $exception): void
    {
        $this->submit->status = SubmitStatus::Error;
        $this->submit->result = SubmitResult::Error;
        $this->submit->save();
    }
}
