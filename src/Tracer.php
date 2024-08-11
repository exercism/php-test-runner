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
    /** Enable to add all events to result JSON */
    private const DEBUG_ALL_EVENTS = false;

    /** Enable to pretty print result JSON */
    private const DEBUG_PRETTY_JSON = false;

    /**
     * Represents the result of the test run for Exercism
     * @see https://exercism.org/docs/building/tooling/test-runners/interface#h-top-level
     * @var array{version: int, status: string, tests: list<Result>, messsage: string}
     */
    private array $result = [
        'version' => 3,
        'status' => 'pass', // 'pass', 'fail', 'error'
        'tests' => [], // internally uses `Result`
        // 'message' => '', // added, when 'status' === 'error'
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
            default => self:: DEBUG_ALL_EVENTS
                ? $this->addUnhandledEvent($event)
                : true
                ,
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
            $this->taskId($testMethod),
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
            $this->taskId($testMethod),
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
            $this->taskId($testMethod),
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
        /** @var Result $result */
        foreach ($this->result['tests'] as $result) {
            if ($result->isFailed() || $result->isErrored()) {
                $this->result['status'] = 'fail';
            }
        }

        \file_put_contents(
            $this->outFileName,
            \json_encode(
                $this->result,
                self::DEBUG_PRETTY_JSON ? JSON_PRETTY_PRINT : 0
            ) . "\n",
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
        $length = $reflectionMethod->getEndLine() - 1 - $start;

        $codeLines = \array_slice(
            \file($reflectionMethod->getFileName()),
            $start,
            $length,
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

    private function taskId(TestMethod $testMethod): int
    {
        $reflectionClass = new ReflectionClass($testMethod->className());
        $reflectionMethod = $reflectionClass->getMethod($testMethod->methodName());
        $docComment = $reflectionMethod->getDocComment();
        if ($docComment === false) {
            return 0;
        }

        $matches=[];
        $matchCount = \preg_match('/@task_id\s+(\d+)/', $docComment, $matches);

        return $matchCount >= 1 ? (int)$matches[1] : 0;
    }
}
