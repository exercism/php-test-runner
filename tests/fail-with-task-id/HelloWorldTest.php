<?php

declare(strict_types=1);

class HelloWorldTest extends PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'HelloWorld.php';
    }

    /** @task_id 99 */
    public function testHelloWorld(): void
    {
        $this->assertEquals('Hello, World!', helloWorld());
    }
}
