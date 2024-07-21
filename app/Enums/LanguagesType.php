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
    const C = 4;


    public static function list()
    {
        return [
            'C++' => LanguagesType::CPlusPlus,
            'C (-std=c17)' => LanguagesType::C,
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
            'C (-std=c17)' => [1, 1],
            'PyPy3.10' => [1.8, 2], // 1.8x more time for pypy and 2x more memory
            'Python3.11' => [2, 2], // 2x more time for python and 2x more memory
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
            case self::C:
                return [
                    File::defaults()
                        ->max('1mb'), // 1 MB
                ];
        }
        return [];
    }
}
