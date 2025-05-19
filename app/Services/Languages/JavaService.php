<?php

namespace App\Services\Languages;

use App\Enums\SubmitResult;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class JavaService extends LanguageService
{
    public function __construct(private string $language) {}

    public function config(): string
    {
        switch ($this->language) {
            case 'Java OpenJDK 24':
            default:
                return '--conf /var/config/basic.conf --exec_file /var/config/runJavaJDK24.sh';
        }
    }

    public function compile(File $code, string $outputName, string $timeoutCompilation): int
    {
        $content = $code->get();
        $className = $this->extractMainPublicClass($content);
        if (! $className) {
            $this->output .= PHP_EOL.'No main class found';

            return SubmitResult::CompilationError;
        }
        Storage::disk('work')->writeStream($className.'.java', $code->readStream());
        Storage::disk('work')->put('manifest.txt', 'Main-Class: '.$className.PHP_EOL);
        switch ($this->language) {
            case 'Java OpenJDK 24':
            default:
                $command = '/langs/javaOpenJDK24/bin/javac -encoding UTF-8 /var/work/'.$className.'.java';
                dump($command);
                exec($command, $this->output, $this->retval);
                if ($this->retval != 0) {
                    break;
                }
                $command = 'cd /var/work && /langs/javaOpenJDK24/bin/jar cfm '.$outputName.'.jar manifest.txt '.$className.'.class && mv '.$outputName.'.jar '.$outputName;
                dump($command);
                exec($command, $this->output, $this->retval);
        }

        if ($this->retval != 0) {
            return SubmitResult::CompilationError;
        }

        return SubmitResult::NoResult;
    }

    private function extractMainPublicClass($contents)
    {
        if (preg_match('/\bpublic\s+class\s+([a-zA-Z_][a-zA-Z0-9_]*)/', $contents, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\bclass\s+([a-zA-Z_][a-zA-Z0-9_]*)/', $contents, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
