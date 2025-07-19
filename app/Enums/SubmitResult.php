<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;


final class SubmitResult extends Enum
{
    const NoResult = 0;
    const Accepted = 1;

    const WrongAnswer = 2;
    const TimeLimit = 3;
    const CompilationError = 4;
    const RuntimeError = 5;
    const MemoryLimit = 6;

    const Error = 7;
    const FileTooLarge = 8;
    const InvalidUtf8File = 9;
    const LanguageNotSupported = 10;
    const NoTestCase = 11;
    const InternalCompilationError = 12;

    const AIDetected = 14;
}
