<?php

namespace App\Support;

class Rupiah
{
    public static function format(float|int|string|null $nilai): string
    {
        return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
    }
}
