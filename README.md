![Smoke Test](https://github.com/exercism/php-test-runner/workflows/Smoke%20Test/badge.svg) ![Tooling image pushed](https://github.com/exercism/php-test-runner/workflows/Deploy/badge.svg)

# PHP Test Runner

This is the test runner for Exercism's v3 platform.
It meets the complete spec for testing all exercises.

## Basic components

### Docker image

The website uses isolated Docker images to run untrusted code in a sandbox.
The image provided by this repository consists of PHP 8.3.10 (PHPUnit 10).
All final assets are built into the image, because the image does not have network access once in use.

Includes PHP extensions: ds, intl

### Test runner

Test running a solution is coordinated by a bash script at `bin/run.sh` taking 3 positional arguments:

```text
bin/run.sh <test-slug> <directory path to solution> <directory path for output>
```

This is what runs inside the production Docker image when students submit their code.

### Testing the test runner

In `./tests/` are golden tests to verify that the test runner behaves as expected.
The CI uses `bin/run-tests.sh` to execute them in the Docker image.

### Running tests locally

Recommended to easily test new test cases during development.
Use `bin/run-locally.sh <test-slug>` to run PHPUnit in your current shell and compare the resulting JSON to `expected_results.json`.
Make sure you have at least PHP 8.1 installed.

### Running tests in Docker locally

This is the recommended way to run all tests locally as they would run in production.
Use `bin/run-tests-in-docker.sh` to locally build and run all tests in the Docker image.
Use `bin/run-in-docker.sh <test-slug> <directory path to solution> <directory path for output>` for a single test run in the Docker image.

### PHPUnit extension

PHPUnit can natively output test results to various formats, but Exercism requires output in a special JSON format.
An extension to PHPUnit is located in `src` folder and registered in `phpunit.xml`.
It provides a tracer for PHPUnit events to produce the required JSON result.
The tracer incorporates `task_id` identification, test code inclusion, and user output dumping.
