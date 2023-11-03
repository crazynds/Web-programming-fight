<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TestCaseType extends Enum
{
    const FileDiff = 0;
    const ProgramCheck = 1;
}
