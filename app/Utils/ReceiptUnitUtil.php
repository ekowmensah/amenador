<?php

namespace App\Utils;

use Illuminate\Support\Str;

class ReceiptUnitUtil
{
    public static function label(string $unit, float $quantity): string
    {
        $unit = trim($unit);
        // Measurement symbols stay unchanged in the plural (for example 2 kg).
        $symbols = ['kg', 'g', 'mg', 'ug', 'µg', 'mcg', 'l', 'ml', 'cl', 'dl', 'oz', 'lb', 'lbs', 'mm', 'cm', 'm', 'km', 'm²', 'm³', '%'];
        if ($quantity <= 1 || $unit === '' || in_array(mb_strtolower($unit), $symbols, true)) {
            return $unit;
        }

        return Str::plural($unit);
    }
}
