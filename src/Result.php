<?php

declare(strict_types=1);

namespace Exercism\PhpTestRunner;

use JsonSerializable;

/**
 * Represents a test result for Exercism
 * @see https://exercism.org/docs/building/tooling/test-runners/interface#h-per-test
 */
final class Result implements JsonSerializable
{
    public function __construct(
        private readonly string $testPrettyName,
        private readonly string $testStatus, // 'pass', 'fail', 'error'
        private readonly string $testCode,
        private readonly int $taskId = 0,
        private string $userOutput = '',
        private readonly string $phpUnitMessage = '',
    ) {
    }

    public function isFailed(): bool
    {
        return $this->testStatus === 'fail';
    }

    public function isErrored(): bool
    {
        return $this->testStatus === 'error';
    }

    public function setUserOutput(string $output): void
    {
        $this->userOutput = $output;
    }

    public function jsonSerialize(): mixed
    {
        $result = [
            'name' => $this->testPrettyName,
            'status' => $this->testStatus,
            'test_code' => $this->testCode,
        ];

        if ($this->taskId !== 0) {
            $result['task_id'] = $this->taskId;
        }

        if ($this->userOutput !== '') {
            $result['output'] = $this->userOutput;
        }

        if ($this->phpUnitMessage !== '') {
            $result['message'] = $this->phpUnitMessage;
        }

        return $result;
    }
}
