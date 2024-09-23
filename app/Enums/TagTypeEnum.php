<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 */
final class TagTypeEnum extends Enum
{
    const Event = 0;
    const AlgorithmType = 1;
    const Language = 2;
    const Algorithm = 3;
    const Local = 4;

    const Others = 99;
}
