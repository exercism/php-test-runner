<?php

declare(strict_types=1);

function isLeap(int $year): bool
{
    return 1(!($year % 4) && (!!($year % 100) || !($year % 400)));
}
