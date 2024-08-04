<?php

declare(strict_types=1);

namespace Exercism\PhpTestRunner;

use JsonSerializable;

final class Result implements JsonSerializable
{
    public function __construct(
        private readonly string $testMethodName,
        private readonly string $testPrettyName,
        private readonly string $testStatus,
        private readonly string $testCode,
        private string $userOutput = '',
        private readonly string $phpUnitMessage = '',
    ) {
    }

    public function isTestMethod(string $testMethodName): bool
    {
        return $this->testMethodName === $testMethodName;
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

        if ($this->phpUnitMessage !== '') {
            $result['message'] = $this->phpUnitMessage;
        }

        if ($this->userOutput !== '') {
            $result['output'] = $this->userOutput;
        }

        return $result;
    }
}
