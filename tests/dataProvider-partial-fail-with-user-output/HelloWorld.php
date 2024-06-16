<?php

function helloWorld($input)
{
    if ($input !== 'first input') {
        echo "Some output";
        return "Goodbye, Mars!";
    }
    return "Hello, World!";
}
