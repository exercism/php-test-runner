<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\TestDox;

class HelloWorldTest extends PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once 'HelloWorld.php';
    }

    /**
     * uuid: af9ffe10-dc13-42d8-a742-e7bdafac449d
     * @task_id 99
     */
    #[TestDox('"Hello, World!" from TestDox attribute')]
    public function testNameDoesNotMatter(): void
    {
        $this->assertEquals('Hello, World!', helloWorld());
    }
}
