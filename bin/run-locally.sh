#!/usr/bin/env bash

vendor/bin/phpunit --do-not-cache-result tests/"${1}"/*Test.php
# Sync'ed from run-tests.sh - Normalize the object ID of `var_dump(new stdClass())`
sed -i -E \
    -e 's/(object\(stdClass\))(#[[:digit:]]+)/\1#79/g' \
    results.json
diff results.json tests/"${1}"/expected_results.json
