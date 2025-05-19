<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;
use Illuminate\Validation\Rules\File;

final class LanguagesType extends Enum
{
    const Auto_detect = 5;

    const CPlusPlus = 0;

    const PyPy3_10 = 1;

    const Python3_11 = 2;

    const C = 4;

    const Python3_13 = 6;

    const PyPy3_11 = 7;

    const Java_OpenJDK24 = 8;

    const BINARY = 99;

    public static function enabled()
    {
        $valids = [
            LanguagesType::Auto_detect,
            LanguagesType::CPlusPlus,
            LanguagesType::C,
            LanguagesType::PyPy3_11,
            LanguagesType::Python3_13,
            LanguagesType::Java_OpenJDK24,
        ];
        $resp = [];
        foreach ($valids as $valid) {
            $resp[LanguagesType::name($valid)] = $valid;
        }

        return $resp;
    }

    public static function list()
    {
        return [
            'Auto detect 95%' => LanguagesType::Auto_detect,
            'C++' => LanguagesType::CPlusPlus,
            'C (-std=c17)' => LanguagesType::C,
            'PyPy3.11' => LanguagesType::PyPy3_11,
            'PyPy3.10' => LanguagesType::PyPy3_10,
            'Python3.11' => LanguagesType::Python3_11,
            'Python3.13' => LanguagesType::Python3_13,    // Not installed yet
            'Java OpenJDK 24' => LanguagesType::Java_OpenJDK24,
        ];
    }

    public static function modifiers()
    {
        // All increments are multiplied by the base values
        // 0 => time
        // 1 => memory
        return [
            'C++' => [1, 1, 0],
            'C (-std=c17)' => [1, 1, 0],
            'PyPy3.10' => [1.8, 2, 0], // 1.8x more time for pypy and 2x more memory
            'PyPy3.11' => [1.8, 2, 0], // 1.8x more time for pypy and 2x more memory
            'Python3.11' => [2, 2, 0], // 2x more time for python and 2x more memory
            'Python3.13' => [1.8, 2, 0], // 1.8x more time for python and 2x more memory
            'Java OpenJDK 24' => [1, 1.2, 1024], // The last value is the extra memory to initialize the JVM
        ];
    }

    public static function name(int $langCode)
    {
        foreach (self::list() as $key => $code) {
            if ($code == $langCode) {
                return $key;
            }
        }

        return self::fromValue($langCode)->key;
    }

    public static function validation(int $langCode)
    {
        switch ($langCode) {
            case self::CPlusPlus:
            case self::PyPy3_10:
            case self::PyPy3_11:
            case self::Python3_11:
            case self::Python3_13:
            case self::C:
            case self::Java_OpenJDK24:
            case self::Auto_detect:
                return [
                    File::defaults()
                        ->max('1mb'), // 1 MB
                ];
            case self::BINARY:
                return [
                    File::defaults()
                        ->max('0mb'), // 0 MB   - Prevent upload of binary file
                ];
        }

        return [];
    }
}
