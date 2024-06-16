<?php

declare (strict_types = 1);

class GigasecondTest extends PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'Gigasecond.php';
    }

    public function dateSetup($date): DateTimeImmutable
    {
        $UTC = new DateTimeZone('UTC');
        return new DateTimeImmutable($date, $UTC);
    }

    public function inputAndExpectedDates(): array
    {
        return [
            ['1959-07-19', '1991-03-27 01:46:40'],
            ['2015-01-24 22:00:00', '2046-10-02 23:46:40'],
        ];
    }

    /**
     * @dataProvider inputAndExpectedDates
     */
    public function testFrom(string $inputDate, string $expected): void
    {
        $date = $this->dateSetup($inputDate);
        $gs = from($date);

        $this->assertSame($expected, $gs->format('Y-m-d H:i:s'));
    }
}
