<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;
use Illuminate\Validation\Rules\File;

final class LanguagesType extends Enum
{
    const CPlusPlus = 0;
    const Python = 1;


    public static function list(){
        return [
            'C++' => LanguagesType::CPlusPlus,
            'Python' => LanguagesType::Python,
        ];
    }

    public static function validation(int $langCode){
        switch($langCode){
            case self::CPlusPlus:
                return [
                    File::defaults()
                        ->max('1mb'), // 1 MB
                ];
            case self::Python:
                return [
                    File::default()
                        ->max('1mb'), // 1 MB
                ];
        }
        return [];
    }
}
