<?php
declare(strict_types=1);

namespace Szemul\SqsQueue\Test\Config;

use Szemul\SqsQueue\Config\QueueConfig;
use PHPUnit\Framework\TestCase;

class QueueConfigTest extends TestCase
{
    public function testGetSqsAttributesWithRequiredOnly(): void
    {
        $sut = new QueueConfig(
            'test',
            true,
            1,
            2,
            3,
            4,
            5,
        );

        $expected = [
            'DelaySeconds'                  => 1,
            'MaximumMessageSize'            => 2,
            'MessageRetentionPeriod'        => 3,
            'ReceiveMessageWaitTimeSeconds' => 4,
            'VisibilityTimeout'             => 5,
        ];

        $this->assertEquals($expected, $sut->getSqsAttributes());
    }

    public function testGetSqsAttributesWithOptional(): void
    {
        $sut = new QueueConfig(
            'test',
            true,
            1,
            2,
            3,
            4,
            5,
            6,
            'testArn',
        );

        $expected = [
            'DelaySeconds'                  => 1,
            'MaximumMessageSize'            => 2,
            'MessageRetentionPeriod'        => 3,
            'ReceiveMessageWaitTimeSeconds' => 4,
            'VisibilityTimeout'             => 5,
            'RedrivePolicy'                 => json_encode([
                'maxReceiveCount'     => 6,
                'deadLetterTargetArn' => 'testArn',
            ]),
        ];

        $this->assertEquals($expected, $sut->getSqsAttributes());
    }
}
