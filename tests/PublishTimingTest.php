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
     */
    public function it_publishes_default()
    {
        $connection = new \AMQPConnection();
        $connection->connect();

        $channel = new \AMQPChannel($connection);

        $exchange = new \AMQPExchange($channel);
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $start = microtime(true);

        for ($i = 0; $i < 10000; $i++) {
            $exchange->publish((string) $i);
        }

        var_dump(__METHOD__, microtime(true) - $start);
    }

    /**
     * @test
     */
    public function it_publishes_transactional()
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
        for ($i = 0; $i < 10000; $i++) {
            $exchange->publish((string) $i);
        }
        $channel->commitTransaction();

        var_dump(__METHOD__, microtime(true) - $start);
    }

    /**
     * @test
     */
    public function it_publishes_confirm_select()
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
            function (int $deliveryTag, bool $multiple) {
                return ($deliveryTag !== 10000);
            },
            function (int $deliveryTag, bool $multiple, bool $requeue) {
                throw new \RuntimeException('Could not publish all events');
            }
        );

        for ($i = 0; $i < 10000; $i++) {
            $exchange->publish((string) $i);
        }
        $channel->waitForConfirm(1);

        var_dump(__METHOD__, microtime(true) - $start);
    }

    /**
     * @test
     */
    public function it_publishes_single_confirm_select()
    {
        $connection = new \AMQPConnection();
        $connection->connect();

        $channel = new \AMQPChannel($connection);

        $exchange = new \AMQPExchange($channel);
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $start = microtime(true);

        for ($i = 0; $i < 10000; $i++) {
            $channel->confirmSelect();
            $channel->setConfirmCallback(
                function (int $deliveryTag, bool $multiple) use ($i) {
                    $i++;
                    return $deliveryTag !== $i;
                },
                function (int $deliveryTag, bool $multiple, bool $requeue) {
                    throw new \RuntimeException('Could not publish all events');
                }
            );
            $exchange->publish((string) $i);
            $channel->waitForConfirm(1);
        }

        var_dump(__METHOD__, microtime(true) - $start);
    }
}