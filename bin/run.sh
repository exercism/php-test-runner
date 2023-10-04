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
  phpunit_output=$(eval "${PHPUNIT_BIN}" \
    -d memory_limit=300M \
    --log-junit "${output_dir%/}/${XML_RESULTS}" \
    --verbose \
    --no-configuration \
    --do-not-cache-result \
    "${test_files%%*( )}" 2>&1)
  phpunit_exit_code=$?
  set -e

  if [[ "${phpunit_exit_code}" -eq 255 ]]; then
    jo version=2 status=error message="${phpunit_output}" tests="[]" > "${output_dir%/}/${JSON_RESULTS}"
    return 0;
  fi

  php junit-handler/run.php \
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
elif [[ ! -d "${2}" ]]; then
  die "Exercise solution directory does not exist"
fi

if [[ -z "${3:-}" ]]; then
  die "Missing exercise test output path"
elif [[ ! -d "${3}" ]]; then
  die "Exercise test output directory does not exist"
fi

deps=("${PHPUNIT_BIN}" tr jo php)
for dep in "${deps[@]}"; do
  installed "${dep}" || die "Missing '${dep}'"
done

main "$@"; exit
