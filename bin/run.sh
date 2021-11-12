#!/usr/bin/env bash

set -euo pipefail

PHPUNIT_BIN="./bin/phpunit-9.phar"
XML_RESULTS='results.xml'
JSON_RESULTS='results.json'

function main {
  exercise_slug="${1}"
  solution_dir="${2}"
  output_dir="${3}"
  test_files=$(find "${solution_dir}" -type f -name '*Test.php' | tr '\n' ' ')

  set +e
  eval "${PHPUNIT_BIN}" \
    --log-junit "${output_dir%/}/${XML_RESULTS}" \
    --verbose \
    --no-configuration \
    --do-not-cache-result \
    "${test_files%%*( )}"
  set -e

  node junit-to-json/dist/index.js \
    "${output_dir%/}/${XML_RESULTS}" \
    "${output_dir%/}/${JSON_RESULTS}"
}

function installed {
  cmd=$(command -v "${1}")

  [[ -n "${cmd}" ]] && [[ -f "${cmd}" ]]
  return ${?}
}

function die {
  >&2 echo "❌ Fatal: ${@}"
  exit 1
}

if [[ -z "${1:-}" ]]; then
  die "Missing exercise slug"
fi

if [[ -z "${2:-}" ]]; then
  die "Missing exercise solution directory path"
elif [ ! -d "${2}" ]; then
  die "Exercise solution directory does not exist"
fi

if [[ -z "${3:-}" ]]; then
  die "Missing exercise test output path"
elif [ ! -d "${3}" ]; then
  die "Exercise test output directory does not exist"
fi

deps=("${PHPUNIT_BIN}" node tr)
for dep in "${deps[@]}"; do
  installed "${dep}" || die "Missing '${dep}'"
done

main "$@"; exit