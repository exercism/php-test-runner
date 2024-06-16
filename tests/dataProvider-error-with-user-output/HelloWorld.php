<?php

function helloWorld()
{
    echo "Some output";
    throw new \BadFunctionCallException("Implement the helloWorld() function");
}
