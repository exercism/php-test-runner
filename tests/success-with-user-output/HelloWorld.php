<?php

function helloWorld()
{
    echo "Some 'user üâ`|| \u{7F} \r\toutput \xFF\xFF\xFF\xFF\xFF\xFF \n"
        . 'containing \\ various "problematic" and UTF-8 chars' . PHP_EOL;
    var_dump(new stdClass());

    return "Hello, World!";
}
