<?php

namespace App\Helpers;

class CodeGenerator
{
    public static function generateBeautifulCode(): int
    {
        $digits = [rand(1, 9), rand(0, 9), rand(0, 9)];
        $patterns = [
            [0, 1, 2, 2, 1, 0], // mirror (123321)
            [0, 0, 1, 1, 2, 2], // pairs (112233)
            [0, 1, 0, 1, 0, 1], // alternating (010101)
            [0, 1, 2, 0, 1, 2], // repeating (123123)
            [0, 0, 0, 1, 1, 1], // halves (000111)
        ];

        $pattern = $patterns[array_rand($patterns)];
        $code = '';

        foreach ($pattern as $p) {
            $code .= $digits[$p];
        }

        return (int) $code;
    }
}
