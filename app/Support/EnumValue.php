<?php

namespace App\Support;

/** Filament may hand back enum-backed select state as an enum instance or as its raw value. */
class EnumValue
{
    public static function of(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
