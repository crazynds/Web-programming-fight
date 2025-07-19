<?php

declare(strict_types=1);

namespace App\Enums;

enum TagTypeEnum: int
{
    use EnumHelpers;
    case Event = 0;
    case AlgorithmType = 1;
    case Language = 2;
    case Algorithm = 3;
    case Local = 4;

    case Others = 99;
}
