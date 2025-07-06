<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\TestDox;

class HelloWorldTest extends PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'HelloWorld.php';
    }

    /** @task_id 99 */
    #[TestDox('"Hello, World!" from TestDox attribute')]
    public function testNameDoesNotMatter(): void
    {
        $this->assertEquals('Hello, World!', helloWorld());
    }
}
