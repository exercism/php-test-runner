<?php

declare(strict_types=1);

namespace Exercism\PhpTestRunner;

use Exercism\PhpTestRunner\Result;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Event;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PrintedUnexpectedOutput;
use PHPUnit\Event\TestRunner\Finished;
use PHPUnit\Event\Tracer\Tracer as TracerInterface;
use ReflectionClass;
use ReflectionMethod;

final class Tracer implements TracerInterface
{
    private array $result = [
        'version' => 3,
        'status' => 'pass',
        'tests' => [],
    ];

    public function __construct(
        private readonly string $fileName,
    ) {
    }

    public function trace(Event $event): void
    {
        match (\get_class($event)) {
            Passed::class => $this->addTestPassed($event),
            Failed::class => $this->addTestFailed($event),
            PrintedUnexpectedOutput::class => $this->addTestOutput($event),
            // default => $this->addUnhandledEvent($event),
            default => true,
        };


        if ($event instanceof Finished) {
            $this->saveResults();
        }
    }

    private function addUnhandledEvent(Event $event): void
    {
        $this->result['tests'][] = new Result(
            $event->asString(),
            'fail',
            'no code yet',
        );
    }

    private function addTestPassed(Passed $event): void
    {
        /** @var TestMethod */
        $testMethod = $event->test();

        $this->result['tests'][] = new Result(
            $testMethod->testDox()->prettifiedMethodName(),
            'pass',
            $this->methodCode($testMethod),
        );
    }

    private function addTestFailed(Failed $event): void
    {
        /** @var TestMethod */
        $testMethod = $event->test();

        $phpUnitMessage = \trim($event->throwable()->asString());
        $phpUnitMessage =\str_replace(
            $testMethod->file(),
            $testMethod->className() . '.php',
            $phpUnitMessage
        );
        $phpUnitMessage = $testMethod->nameWithClass() . "\n" . $phpUnitMessage;

        if (!empty($message)) {
            $message = PHP_EOL . $message;
        }

        $this->result['tests'][] = new Result(
            $testMethod->testDox()->prettifiedMethodName(),
            'fail',
            $this->methodCode($testMethod),
            '',
            $phpUnitMessage,
        );
    }

    private function addTestOutput(PrintedUnexpectedOutput $event): void
    {
        // This must rely on the sequence of events!

        /** @var Result */
        $lastTest = $this->result['tests'][\array_key_last($this->result['tests'])];
        $lastTest->setUserOutput($event->output());
    }

    private function saveResults(): void
    {
        foreach ($this->result['tests'] as $result) {
            if ($result->isFailed()) {
                $this->result['status'] = 'fail';
            }
        }

        \file_put_contents(
            $this->fileName,
            \json_encode($this->result) . "\n",
            // \json_encode($this->result, JSON_PRETTY_PRINT) . "\n",
        );
    }

    private function methodCode(TestMethod $testMethod): string
    {
        $reflectionClass = new ReflectionClass($testMethod->className());
        $reflectionMethod = $reflectionClass->getMethod($testMethod->methodName());

        // Line numbers are 1-based, array index is 0-based.
        // Reflections start line is the function declaration, end line is
        // closing curly bracket.
        // We use PSR-12, which makes line based code extraction problematic
        // (function parameters may be on multiple lines). But we have 99% of
        // code starting on second line after function declaration, and the
        // closing bracket will be on the line after the last code line.
        $start = $reflectionMethod->getStartLine() - 1 + 2;
        $end = $reflectionMethod->getEndLine() - 1 - 1;

        $codeLines = \array_filter(
            \file($reflectionMethod->getFileName()),
            static fn ($index) => $index >= $start && $index <= $end,
            ARRAY_FILTER_USE_KEY,
        );

        // Unindent lines 2 levels of 4 spaces each
        $codeLines = \array_map(
            fn ($line) => \substr($line, 2 * 4),
            $codeLines,
        );

        return \implode('', $codeLines);
    }
}
