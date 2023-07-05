<?php

require __DIR__ . '/vendor/autoload.php';

$xml_in = $argv[1];
$json_out = $argv[2];

$handler = new \Exercism\JunitHandler\Handler();
$handler->run($xml_in, $json_out);
