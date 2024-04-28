<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;
use Illuminate\Validation\Rules\File;

final class LanguagesType extends Enum
{
    const CPlusPlus = 0;
    const PyPy3_10 = 1;
    const Python3_11 = 2;


    public static function list()
    {
        return [
            'C++' => LanguagesType::CPlusPlus,
            'PyPy3.10' => LanguagesType::PyPy3_10,
            'Python3.11' => LanguagesType::Python3_11,
        ];
    }

    public static function modifiers()
    {
        // All increments are multiplied by the base values
        // 0 => time
        // 1 => memory
        return [
            'C++' => [1, 1],
            'PyPy3.10' => [1.2, 2], // 1.2x more time for python and 2 x more memory
            'Python3.11' => [1.5, 2], // 1.5x more time for python and 2 x more memory
        ];
    }

    public static function name(int $langCode)
    {
        foreach (self::list() as $key => $code) {
            if ($code == $langCode)
                return $key;
        }
    }

    public static function validation(int $langCode)
    {
        switch ($langCode) {
            case self::CPlusPlus:
            case self::PyPy3_10:
            case self::Python3_11:
                return [
                    File::defaults()
                        ->max('1mb'), // 1 MB
                ];
        }
        return [];
    }
}
