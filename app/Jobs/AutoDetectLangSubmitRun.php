<?php

namespace App\Jobs;

use App\Enums\LanguagesType;
use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Models\SubmitRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class AutoDetectLangSubmitRun implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected SubmitRun $submitRun
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->submitRun->status = SubmitStatus::DetectingLang;
        $this->submitRun->save();
        Storage::disk('nsjail')->writeStream('0.file', $this->submitRun->file->readStream());
        $comand = "python3 /var/scripts/autolang.py < /var/nsjail/0.file";
        exec($comand, $output, $retval);
        switch ($output[0]) {
            case 'Python':
                $this->submitRun->language = LanguagesType::PyPy3_10;
                break;
            case 'C':
            case 'C++':
                $this->submitRun->language = LanguagesType::CPlusPlus;
                break;
            default:
                $this->submitRun->language = LanguagesType::Not_found;
                $this->submitRun->status = SubmitStatus::Error;
                $this->submitRun->result = SubmitResult::LanguageNotSupported;
                $this->submitRun->save();
                return;
        }
        $this->submitRun->status = SubmitStatus::WaitingInLine;
        $this->submitRun->save();
        ExecuteSubmitJob::dispatch($this->submitRun)->onQueue($this->queue);
    }
}
