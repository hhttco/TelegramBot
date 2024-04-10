<?php

namespace App\Utils;

class Helper {
    public static function generateOrderNo(): string
    {
        $randomChar = rand(10000, 99999);
        return date('YmdHms') . $randomChar;
    }
}
