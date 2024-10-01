<?php

function helloWorld()
{
    $invalidChars = [
        "\u{7F}", // Delete
        "\xFF\xFF\xFF\xFF\xFF\xFF", // Invalid UTF-8
    ];

    return 'Handle invalid chars: ' . implode(' ', $invalidChars) . '!';
}
