<?php

require __DIR__ . '/vendor/autoload.php';

$json_out = $argv[1];
$xml_in = $argv[2];
$teamcity_in = $argv[3];

$handler = new \Exercism\JunitHandler\Handler();
$handler->run($json_out, $xml_in, $teamcity_in);
