<?php

namespace App\Services\Languages;

use App\Enums\SubmitResult;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class PythonService extends LanguageService
{
    public function __construct(private string $language) {}

    public function config(): string
    {
        switch ($this->language) {
            case 'PyPy3.10':
                return '--conf /var/config/python.conf -R /var/config/runPypy3.10.sh --exec_file /var/config/runPypy3.10.sh';
            case 'PyPy3.11':
                return '--conf /var/config/python.conf -R /var/config/runPypy3.11.sh --exec_file /var/config/runPypy3.11.sh';
            case 'Python3.11':
                return '--conf /var/config/python.conf -R /var/config/runPython3.11.sh --exec_file /var/config/runPython3.11.sh';
            case 'Python3.13':
            default:
                return '--conf /var/config/python.conf -R /var/config/runPython3.13.sh --exec_file /var/config/runPython3.13.sh';
        }
    }

    public function compile(File $code, string $outputName, string $timeoutCompilation): int
    {
        Storage::disk('work')->deleteDirectory('__pycache__');
        Storage::disk('work')->writeStream($outputName, $code->readStream());
        switch ($this->language) {
            case 'PyPy3.10':
                $command = 'pypy3.10 -m py_compile /var/work/'.$outputName;
                break;
            case 'PyPy3.11':
                $command = 'pypy3.11 -m py_compile /var/work/'.$outputName;
                break;
            case 'Python3.11':
                $command = 'python3 -m py_compile /var/work/'.$outputName;
                break;
            case 'Python3.13':
            default:
                $command = 'python3.13 -m py_compile /var/work/'.$outputName;
        }
        exec($command, $this->output, $this->retval); // Compile pypy

        return SubmitResult::NoResult;
    }
}
