<?php

namespace Exercism\JunitHandler;

use phpowermove\docblock\Docblock;
use ReflectionClass;
use ReflectionMethod;
use SimpleXMLElement;

class Handler
{
    private const VERSION = 3;

    private const STATUS_ERROR = 'error';
    private const STATUS_PASS = 'pass';
    private const STATUS_FAIL = 'fail';

    public function run(string $xml_path, $json_path): void
    {
        $testsuites = simplexml_load_file($xml_path);
        if ($testsuites === false) {
            $output = [
                'version' => self::VERSION,
                'tests' => [],
                'status' => self::STATUS_ERROR,
                'message' => <<<ERROR_MESSAGE
                    Test run did not produce any output. Check your code to see if the code exits unexpectedly before the report is generated.

                    E.g. Using the `die` function will cause the test runner to exist unexpectedly.
                    ERROR_MESSAGE
            ];
            $this->write_json($json_path, $output);
            return;
        }

        $testsuite = $testsuites->testsuite;
        $testsuite_attrs = $testsuite->attributes();

        $test_class = $testsuite_attrs['name'];
        $test_file_path = $testsuite_attrs['file'];

        $testcase_error_count = (int) $testsuite_attrs['errors'];
        $testcase_failure_count = (int) $testsuite_attrs['failures'];

        $test_file_source = explode("\n", file_get_contents($test_file_path));
        $reflection_test_class =
        $this->getReflectionTestClass($test_class, $test_file_path);

        $output = [
            'version' => self::VERSION,
            'status' => ($testcase_error_count !== 0 || $testcase_failure_count !== 0)
            ? self::STATUS_FAIL
            : self::STATUS_PASS,
            'tests' =>
            $this->parseTestSuite(
                $testsuite,
                $reflection_test_class,
                $test_file_source
            ),
        ];

        $this->write_json($json_path, $output);
    }

    /**
     * @param string[] $test_file_source
     */
    private function parseTestSuite(
        SimpleXMLElement $testsuite,
        ReflectionClass $test_class,
        array $test_file_source
    ): array {
        $testcase_methods_by_name = [];
        foreach ($test_class->getMethods() as $method) {
            $testcase_methods_by_name[$method->getName()] = $method;
        }

        $testcase_outputs = [];
        $nested_testsuites = $testsuite->testsuite;
        foreach ($nested_testsuites as $nested_testsuite) {
            $testcase_outputs[] = $this->parseNestedTestSuite(
                testsuite: $nested_testsuite,
                test_class: $test_class,
                test_file_source: $test_file_source,
                test_methods_by_name: $testcase_methods_by_name
            );
        }

        $testcase_outputs[] = $this->parseTestCases(
            testsuite: $testsuite,
            test_class: $test_class,
            test_file_source: $test_file_source,
            test_methods_by_name: $testcase_methods_by_name
        );

        return array_merge(...$testcase_outputs);
    }

    /**
     * @param string[] $test_file_source
     * @param array<string, ReflectionMethod> $test_methods_by_name
     */
    private function parseNestedTestSuite(
        SimpleXMLElement $testsuite,
        ReflectionClass $test_class,
        array $test_file_source,
        array $test_methods_by_name,
    ): array {
        $attrs = $testsuite->attributes();
        $name = (string) $attrs['name'];
        $test_method_name =
            ltrim(substr($name, (int) strpos($name, "::")), "::");

        return $this->parseTestCases(
            testsuite: $testsuite,
            test_class: $test_class,
            test_file_source: $test_file_source,
            test_methods_by_name: $test_methods_by_name,
            supplied_method_name: $test_method_name,
        );
    }

    /**
     * @param string[] $test_file_source
     * @param array<string, ReflectionMethod> $test_methods_by_name
     */
    private function parseTestCases(
        SimpleXMLElement $testsuite,
        ReflectionClass $test_class,
        array $test_file_source,
        array $test_methods_by_name,
        ?string $supplied_method_name = null
    ): array {
        $testcase_outputs = [];
        foreach ($testsuite->testcase as $testcase) {
            $attrs = $testcase->attributes();
            $name = (string) $attrs['name'];
            /** @var ReflectionMethod $method */
            $method = $test_methods_by_name[$supplied_method_name ?? $name];
            $docblock = new Docblock($method->getDocComment());

            $output = [
                'name' => $name,
                'status' => self::STATUS_PASS,
                'test_code' => $this->getTestCaseSource(
                    method: $method,
                    test_source: $test_file_source
                ),
            ];

            $task_id_tags = $docblock->getTags('task_id')->toArray();
            if ($task_id_tags) {
                $tag = $task_id_tags[0];
                $output['task_id'] = (int) ($tag->getDescription());
            }

            $testdoxi = $docblock->getTags('testdox')->toArray();
            if ($testdoxi) {
                $testdox = $testdoxi[0];
                $output['name'] = $testdox->getDescription();
            }

            foreach ($testcase->children() ?? [] as $name => $data) {
                if ($name === 'system-out') {
                    $output['output'] = (string) $data;
                } elseif ($name === 'error') {
                    $output['status'] = self::STATUS_ERROR;
                    $output['message'] = (string) $data;
                } elseif ($name === 'failure') {
                    $output['status'] = self::STATUS_FAIL;
                    $output['message'] = (string) $data;
                }
            }

            $testcase_outputs[] = $output;
        }

        return $testcase_outputs;
    }

    private function write_json(string $json_path, array $output): void
    {
        $json = json_encode(
            value: $output,
            flags: JSON_THROW_ON_ERROR
        );
        file_put_contents($json_path, $json . "\n");
    }

    private function getReflectionTestClass(
        string $test_class,
        string $test_file_path
    ): ReflectionClass {
        require_once $test_file_path;
        $class = new ReflectionClass($test_class);
        return $class;
    }

    private function getTestCaseSource(
        ReflectionMethod $method,
        array $test_source,
    ): string {
        $source_start_line = $method->getStartLine();
        $source_end_line = $method->getEndLine();
        $has_lines = !in_array(
            needle: false,
            haystack: [$source_start_line, $source_end_line],
            strict: true
        );
        $test_lines = $has_lines
        ? array_slice(
            $test_source,
            $source_start_line + 1,
            $source_end_line - 2 - $source_start_line
        )
        : ['Unable to obtain test code.'];

        $min_indent = PHP_INT_MAX;
        foreach ($test_lines as $line) {
            if (empty($line)) {
                continue;
            }
            $indent = strlen($line) - strlen(ltrim($line));
            if ($indent < $min_indent) {
                $min_indent = $indent;
            }
        }

        foreach ($test_lines as $idx => $line) {
            $test_lines[$idx] = substr($line, $min_indent);
        }

        return implode("\n", $test_lines) . "\n";
    }
}
