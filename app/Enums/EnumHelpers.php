<?php

namespace App\Enums;

trait EnumHelpers
{
    private static function formatKey($key)
    {
        $formatted = str_replace('_', ' ', $key);
        $formatted = str_replace('-', ' ', $formatted);

        $formatted = preg_replace('/(?<!^)([A-Z][a-z])/', ' $1', $formatted);

        if (strtoupper(str_replace(' ', '', $formatted)) === str_replace(' ', '', $formatted)) {
            return strtoupper($formatted);
        }

        return ucwords($formatted);
    }

    public static function getRandomValue()
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }

    public static function fromName(string $name): ?\stdClass
    {
        foreach (self::cases() as $status) {
            if ($name === $status->name) {
                $obj = new \stdClass;
                $obj->value = $status->value;
                $obj->key = $status->name;
                $obj->description = self::formatKey($status->name);

                return $obj;
            }
        }

        return null;
    }

    public static function fromValue($value): ?\stdClass
    {
        foreach (self::cases() as $status) {
            if ($value === $status->value) {
                $obj = new \stdClass;
                $obj->value = $status->value;
                $obj->key = $status->name;
                $obj->description = self::formatKey($status->name);

                return $obj;
            }
        }

        return null;
    }

    public static function getInstances()
    {
        $instances = [];
        foreach (self::cases() as $status) {
            $obj = new \stdClass;
            $obj->value = $status->value;
            $obj->key = $status->name;
            $obj->description = self::formatKey($status->name);
            $instances[] = $obj;
        }

        return $instances;
    }

    public function description()
    {
        return self::formatKey($this->name);
    }
}
