![Jest Tests](https://github.com/exercism/php-test-runner/workflows/Test%20JUnit-to-JSON/badge.svg) ![Smoke Test](https://github.com/exercism/php-test-runner/workflows/Smoke%20Test/badge.svg) ![Tooling image pushed](https://github.com/exercism/php-test-runner/workflows/Push%20Docker%20images%20to%20DockerHub%20and%20ECR/badge.svg)

# PHP Test Runner

This is a minimal test runner for Exercism's v3 platform.  It meets the minimal spec for testing _practice exercises_.  It does not currently parse the test case code being run, therefore it does not meet the standard for testing _concept exercises_.

## Basic components

### Dockerimage

The website uses isolated docker images to run untrusted code in a sandbox.  Image consists of PHP 8.3.3 (PHPUnit 9/10). All final assets are built into the image, because the image does not have network access once in use.

Includes php extensions: ds, intl

### Test runner

Test running a solution is coordinated by a bash script at `bin/run.sh` taking 3 positional arguments:

```text
> bin/run.sh <test-slug> <directory path to solution> <directory path for output>
```

### JUnit to JSON

PHPUnit can natively output tests run to junit xml format, but Exercism requires output in json format. A php-based app is located in the `junit-handler` folder. It provides a translation layer from one format to the other incorporating task_id identification and test code inclusion.
