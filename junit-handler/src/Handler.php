<?php

namespace Exercism\JunitHandler;

use ReflectionClass;
use SimpleXMLElement;
use phpowermove\docblock\Docblock;

class Handler {
    private const VERSION = 2;
    
    private const STATUS_ERROR = 'error';
    private const STATUS_PASS= 'pass';
    private const STATUS_FAIL= 'fail';

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

        $reflection_test_class = $this->getReflectionTestClass($test_class, $test_file_path);

        $output = [
            'version' => self::VERSION,
            'status' => ($testcase_error_count !== 0 || $testcase_failure_count !== 0)
                ? self::STATUS_FAIL
                : self::STATUS_PASS,
            'tests' => $this->parseTestCases($testsuite, $reflection_test_class)
        ];

        $this->write_json($json_path, $output);
    }

    private function write_json(string $json_path, array $output): void
    {
        $json = json_encode(
            value: $output,
            flags: JSON_THROW_ON_ERROR
        );
        file_put_contents($json_path, $json."\n");
    }

    private function getReflectionTestClass(string $test_class, string $test_file_path): ReflectionClass
    {
        require_once($test_file_path);
        $class = new ReflectionClass($test_class);
        return $class;
    }

    private function parseTestCases(SimpleXMLElement $testsuite, ReflectionClass $test_class): array
    {
        $testcase_methods_by_name = [];
        foreach ($test_class->getMethods() as $method) {
            $testcase_methods_by_name[$method->getName()] = $method;
        }

        $testcase_outputs = [];
        foreach ($testsuite->testcase as $testcase) {
            $attrs = $testcase->attributes();
            $name = (string) $attrs['name'];
            $method = $testcase_methods_by_name[$name];
            $docblock = new Docblock($method->getDocComment());
            
            $output = [
                'name' => $name,
                'status' => self::STATUS_PASS
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
}
