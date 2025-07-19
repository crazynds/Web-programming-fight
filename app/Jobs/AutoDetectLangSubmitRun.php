<?php

namespace App\Jobs;

use App\Enums\LanguagesType;
use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AutoDetectLangSubmitRun implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Submission $submission
    ) {
        $this->onQueue('submit');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->submission->status = SubmitStatus::DetectingLang;
        $this->submission->save();
        Storage::disk('work')->writeStream('0.code', $this->submission->file->readStream());
        $comand = 'python3 /var/scripts/autolang.py < /var/work/0.code';
        exec($comand, $output, $retval);
        switch ($output[0]) {
            case 'Python':
                $this->submission->language = LanguagesType::PyPy3_11;
                break;
            case 'C':
            case 'C++':
                $this->submission->language = LanguagesType::CPlusPlus;
                break;
            default:
                $this->submission->language = LanguagesType::Auto_detect;
                $this->submission->status = SubmitStatus::Error;
                $this->submission->result = SubmitResult::LanguageNotSupported;
                $this->submission->save();

                return;
        }
        $this->submission->status = SubmitStatus::WaitingInLine;
        $this->submission->save();
        ExecuteSubmitJob::dispatch($this->submission)->onQueue($this->queue);
    }

    public function failed(Throwable $exception): void
    {
        $this->submission->status = SubmitStatus::Error;
        $this->submission->result = SubmitResult::Error;
        $this->submission->output = $exception->getTraceAsString();
        $this->submission->save();
    }
}
