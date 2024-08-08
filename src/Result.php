<?php

declare(strict_types=1);

namespace Exercism\PhpTestRunner;

use JsonSerializable;

final class Result implements JsonSerializable
{
    public function __construct(
        private readonly string $testPrettyName,
        private readonly string $testStatus,
        private readonly string $testCode,
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

        if ($this->userOutput !== '') {
            $result['output'] = $this->userOutput;
        }

        if ($this->phpUnitMessage !== '') {
            $result['message'] = $this->phpUnitMessage;
        }

        return $result;
    }
}
