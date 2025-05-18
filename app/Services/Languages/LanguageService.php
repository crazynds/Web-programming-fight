<?php

namespace App\Services\Languages;

use App\Models\File;

abstract class LanguageService
{
    public $retval = 0;

    public $output = '';

    abstract public function config(): string;

    abstract public function compile(File $code, string $outputName, string $timeoutCompilation): int;
}
