<?php

declare(strict_types=1);

function isLeap(int $year): bool
{
    return !($year % 4) && (!!($year % 100) || !($year % 400));
}
