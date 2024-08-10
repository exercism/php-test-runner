<?php

declare(strict_types=1);

namespace Exercism\PhpTestRunner;

use Exercism\PhpTestRunner\Result;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Event;
use PHPUnit\Event\Test\BeforeFirstTestMethodErrored;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PrintedUnexpectedOutput;
use PHPUnit\Event\TestRunner\Finished;
use PHPUnit\Event\Tracer\Tracer as TracerInterface;
use ReflectionClass;

final class Tracer implements TracerInterface
{
    private array $result = [
        'version' => 3,
        'status' => 'pass',
        'tests' => [],
    ];

    public function __construct(
        private readonly string $outFileName,
        private readonly string $exerciseDir,
    ) {
    }

    public function trace(Event $event): void
    {
        match (\get_class($event)) {
            Passed::class => $this->addTestPassed($event),
            Failed::class => $this->addTestFailed($event),
            Errored::class => $this->addTestErrored($event),
            BeforeFirstTestMethodErrored::class => $this->addBeforeFirstTestMethodErrored($event),
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
        $phpUnitMessage = \str_replace(
            $this->exerciseDir . '/',
            '',
            $phpUnitMessage
        );
        $phpUnitMessage = $testMethod->nameWithClass() . "\n" . $phpUnitMessage;

        $this->result['tests'][] = new Result(
            $testMethod->testDox()->prettifiedMethodName(),
            'fail',
            $this->methodCode($testMethod),
            '',
            $phpUnitMessage,
        );
    }

    private function addTestErrored(Errored $event): void
    {
        /** @var TestMethod */
        $testMethod = $event->test();

        $phpUnitMessage = \trim($event->throwable()->asString());
        $phpUnitMessage = \str_replace(
            $this->exerciseDir . '/',
            '',
            $phpUnitMessage
        );
        $phpUnitMessage = $testMethod->nameWithClass() . "\n" . $phpUnitMessage;

        $this->result['tests'][] = new Result(
            $testMethod->testDox()->prettifiedMethodName(),
            'error',
            $this->methodCode($testMethod),
            '',
            $phpUnitMessage,
        );
    }

    private function addBeforeFirstTestMethodErrored(BeforeFirstTestMethodErrored $event): void
    {
        $phpUnitMessage = \trim($event->throwable()->asString());
        $phpUnitMessage = \str_replace(
            $this->exerciseDir . '/',
            '',
            $phpUnitMessage
        );

        $this->result['status'] = 'error';
        $this->result['message'] = $phpUnitMessage;
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
            if ($result->isFailed() || $result->isErrored()) {
                $this->result['status'] = 'fail';
            }
        }

        \file_put_contents(
            $this->outFileName,
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

        // Unindent lines 2 levels of 4 spaces each (if possible)
        $codeLines = \array_map(
            fn ($line) => \str_starts_with($line, '        ')
                ? \substr($line, 2 * 4)
                : $line
                ,
            $codeLines,
        );

        return \implode('', $codeLines);
    }
}
