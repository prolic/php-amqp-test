<?php

declare (strict_types=1);

namespace Prolic\PhpAmqpTest;

use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class PersistentConnectionTest
 * @package Prolic\PhpAmqpTest
 */
class PersistentConnectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_uses_persistent_connection()
    {
        $connection = new \AMQPConnection();
        $connection->pconnect();

        $connection2 = new \AMQPConnection();
        $connection2->pconnect();
    }
}
