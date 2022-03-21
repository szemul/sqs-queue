<?php
declare(strict_types=1);

namespace Szemul\SqsQueue\Test\Message;

use Szemul\SqsQueue\Message\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testGetters(): void
    {
        $sut = new Message(
            'testJob',
            ['foo' => 'bar'],
            'queueId',
            'receiptHandle',
            ['foo'       => ['StringValue' => 'fooBar', 'DataType' => 'String']],
            ['queueName' => ['StringValue' => 'test', 'DataType' => 'String']],
        );

        $this->assertSame(['foo' => ['StringValue' => 'fooBar', 'DataType' => 'String']], $sut->getAttributes());
        $this->assertSame(
            ['queueName' => ['StringValue' => 'test', 'DataType' => 'String']],
            $sut->getMessageAttributes(),
        );
        $this->assertSame('receiptHandle', $sut->getReceiptHandle());
    }
}
