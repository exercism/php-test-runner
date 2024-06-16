<?php

function from(DateTimeImmutable $date): DateTimeImmutable
{
    echo "Some output";
    throw new \BadFunctionCallException("Implement the from function");
}
