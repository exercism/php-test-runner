#!/usr/bin/env bash

vendor/bin/phpunit --do-not-cache-result tests/"${1}"/*Test.php
diff results.json tests/"${1}"/expected_results.json
