<?php

declare(strict_types=1);

class HelloWorldTest extends PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'HelloWorld.php';
    }

    static public function input(): array
    {
        return [
            ['first input'],
            ['second input'],
        ];
    }

    /** @dataProvider input */
    public function testHelloWorld($input): void
    {
        $this->assertEquals('Hello, World!', helloWorld($input));
    }
}
