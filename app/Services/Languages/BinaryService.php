<?php

namespace App\Services\Languages;

use App\Enums\SubmitResult;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class BinaryService extends LanguageService
{
    public function config(): string
    {
        return '--conf /var/config/basic.conf -R /var/config/runBinary.sh --exec_file /var/config/runBinary.sh';
    }

    public function compile(File $code, string $outputName, string $timeoutCompilation): int
    {
        Storage::disk('work')->writeStream($outputName, $code->readStream());

        return SubmitResult::NoResult;
    }
}
