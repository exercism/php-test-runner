<?php

function helloWorld()
{
    echo "Some 'user üâ`|| \r\toutput\n"
        . 'containing \\ various "problematic" and UTF-8 chars' . PHP_EOL;
    var_dump(new stdClass());

    return "Hello, World!";
}
