<?php

namespace App\Services\Languages;

use App\Enums\SubmitResult;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class CPPService extends LanguageService
{
    public function config(): string
    {
        return '--conf /var/config/basic.conf -R /var/config/runBinary.sh --exec_file /var/config/runBinary.sh';
    }

    public function compile(File $code, string $outputName, string $timeoutCompilation): int
    {
        $program = 'prog.cpp';
        Storage::disk('work')->writeStream($program, $code->readStream());
        exec("timeout $timeoutCompilation bash /var/config/compile.sh c++ /var/work/'$program' /var/work/'$outputName'", $this->output, $this->retval);
        // exec("g++ -std=c++20 -mtune=native -Wreturn-type -static -march=native -w -O2 /var/work/'$program' -o /var/work/'$outputName' 2>&1", $this->output, $this->retval);  // Old method
        if ($this->retval == 124) {
            $this->output .= PHP_EOL.'Compilation timed out';
        }
        if ($this->retval != 0) {
            return SubmitResult::CompilationError;
        }

        return SubmitResult::NoResult;
    }
}
