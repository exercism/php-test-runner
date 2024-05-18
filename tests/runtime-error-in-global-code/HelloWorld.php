<?php

declare(strict_types=1);

function helloWorld(int $value)
{
    throw new \BadFunctionCallException("Implement the helloWorld() function");
}

// Causes a type error at runtime only, outside of a test function
helloWorld('string');
