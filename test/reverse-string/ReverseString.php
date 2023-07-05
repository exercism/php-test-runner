<?php

declare (strict_types = 1);

function reverseString(string $text): string
{
    echo $text;

    $encoding = mb_detect_encoding($text);
    $length = mb_strlen($text, $encoding);
    $reversed = '';
    while ($length-- > 0) {
        $reversed .= mb_substr($text, $length, 1, $encoding);
    }

    return $reversed;
}
