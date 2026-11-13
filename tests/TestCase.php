<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $host = config("database.connections.{$connection}.host");

        if ($connection !== 'mysql' || $database !== 'lms_testing' || ! in_array($host, ['127.0.0.1', 'localhost'], true)) {
            $this->fail('Tests refused because the connection is not the local disposable lms_testing database.');
        }
    }
}
