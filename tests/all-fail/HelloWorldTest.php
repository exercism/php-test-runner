<?php

declare(strict_types=1);

class HelloWorldTest extends PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'HelloWorld.php';
    }

    public function testHelloWorldOne(): void
    {
        $this->assertEquals('Hello, World!', helloWorld());
    }

    public function testHelloWorldTwo(): void
    {
        $this->assertEquals('Hello, World!', helloWorld());
    }

    public function testHelloWorldThree(): void
    {
        $this->assertEquals('Hello, World!', helloWorld());
    }
}
