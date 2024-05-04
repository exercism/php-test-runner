<?php

declare(strict_types=1);

namespace Exercism\JunitHandler;

final class TeamcityResult
{
    private const OUTPUT_LINE_START = '##teamcity[testFailed';
    private const OUTPUT_FIELD_START = "message='This test printed output: ";
    private const OUTPUT_FIELD_END = "' details='";
    private const NAME_FIELD = "name='";

    private ?array $outputCollection = null;

    public function __construct(
        private readonly string $teamcityFile,
    ) {
        $this->fillOutputCollection();
    }

    public function hasResults(): bool
    {
        return $this->outputCollection !== null;
    }

    public function hasOutputOf(string $method): bool
    {
        return isset($this->outputCollection[$method]);
    }

    public function outputOf(string $method): ?string
    {
        return $this->outputCollection[$method] ?? null;
    }

    private function fillOutputCollection(): void
    {
        try {
            $linesWithOutput = \array_filter(
                \file($this->teamcityFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES),
                $this->islineWithOutput(...)
            );
            $this->outputCollection = \array_combine(
                $this->testNamesFrom($linesWithOutput),
                $this->outputFrom($linesWithOutput),
            );
        } catch (\Throwable $exception) {
            // Intentionally empty.
        }
    }

    private function islineWithOutput(string $line): bool
    {
        return \str_starts_with($line, self::OUTPUT_LINE_START)
            && \str_contains($line, self::OUTPUT_FIELD_START)
            ;
    }

    private function testNamesFrom(array $lines): array
    {
        return \array_map($this->testNameFromThisLine(...), $lines);
    }

    private function testNameFromThisLine(string $line): string
    {
        $startOfTestName = \mb_strpos($line, self::NAME_FIELD) + \mb_strlen(self::NAME_FIELD);
        $endOfTestName = \mb_strpos($line, "'", $startOfTestName);

        return \mb_substr($line, $startOfTestName, $endOfTestName - $startOfTestName);
    }

    private function outputFrom(array $lines): array
    {
        return \array_map($this->outputFromThisLine(...), $lines);
    }

    private function outputFromThisLine(string $line): string
    {
        $startOfOutput = \mb_strpos($line, self::OUTPUT_FIELD_START) + \mb_strlen(self::OUTPUT_FIELD_START);
        $endOfOutput = \mb_strpos($line, self::OUTPUT_FIELD_END, $startOfOutput);
        $rawOutput = \mb_substr($line, $startOfOutput, $endOfOutput - $startOfOutput);

        return $this->unescape($rawOutput);
    }

    private function unescape(string $text): string
    {
        return \str_replace(
            // Keep this in sync with PHPUnit Teamcity escape()
            // https://github.com/sebastianbergmann/phpunit/blob/main/src/Logging/TeamCity/TeamCityLogger.php#L331
            ['||', "|'", '|n', '|r', '|]', '|['],
            ['|', "'", "\n", "\r", ']', '['],
            $text,
        );
    }
}
