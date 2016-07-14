<?php

declare (strict_types=1);

namespace Prolic\PhpAmqpTest;

use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class PublishTimingTest
 * @package Prolic\PhpAmqpTest
 */
class PublishTimingTest extends TestCase
{
    /**
     * @test
     * @dataProvider dataProvider
     */
    public function it_publishes_default(int $counter)
    {
        $connection = new \AMQPConnection();
        $connection->connect();

        $channel = new \AMQPChannel($connection);

        $exchange = new \AMQPExchange($channel);
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $start = microtime(true);

        for ($i = 0; $i < $counter; $i++) {
            $exchange->publish((string) $i);
        }

        var_dump(__METHOD__, $counter, microtime(true) - $start);
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function it_publishes_transactional(int $counter)
    {
        $connection = new \AMQPConnection();
        $connection->connect();

        $channel = new \AMQPChannel($connection);

        $exchange = new \AMQPExchange($channel);
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $start = microtime(true);

        $channel->startTransaction();
        for ($i = 0; $i < $counter; $i++) {
            $exchange->publish((string) $i);
        }
        $channel->commitTransaction();

        var_dump(__METHOD__, $counter, microtime(true) - $start);
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function it_publishes_confirm_select(int $counter)
    {
        $connection = new \AMQPConnection();
        $connection->connect();

        $channel = new \AMQPChannel($connection);

        $exchange = new \AMQPExchange($channel);
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $start = microtime(true);

        $channel->confirmSelect();
        $channel->setConfirmCallback(
            function (int $deliveryTag, bool $multiple) use ($counter) {
                return ($deliveryTag !== $counter);
            },
            function (int $deliveryTag, bool $multiple, bool $requeue) {
                throw new \RuntimeException('Could not publish all events');
            }
        );

        for ($i = 0; $i < $counter; $i++) {
            $exchange->publish((string) $i);
        }
        $channel->waitForConfirm(1);

        var_dump(__METHOD__, $counter, microtime(true) - $start);
    }

    public function dataProvider()
    {
        return [
            [10],
            [100],
            [1000],
            [10000],
            [100000],
            [1000000]
        ];
    }
}