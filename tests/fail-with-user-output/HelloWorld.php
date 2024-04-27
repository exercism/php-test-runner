<?php

function helloWorld()
{
    echo "Some 'user üâ`|| output" . PHP_EOL
        . 'containing \\ various "problematic" and UTF-8 chars' . PHP_EOL;
    var_dump(new stdClass());

    return "Goodbye, Mars!";
}
