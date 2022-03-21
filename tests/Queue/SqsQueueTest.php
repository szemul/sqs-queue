<?php
declare(strict_types=1);

namespace Szemul\SqsQueue\Test\Queue;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use RuntimeException;
use Szemul\Queue\Exception\PublishingFailedException;
use Szemul\Queue\Message\MessageInterface;
use Szemul\SqsQueue\Config\QueueConfig;
use Szemul\SqsQueue\Message\Message;
use Szemul\SqsQueue\Queue\SqsQueue;
use PHPUnit\Framework\TestCase;
use Szemul\SqsQueue\Sqs;

/** @covers \Szemul\SqsQueue\Queue\SqsQueue */
class SqsQueueTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private Sqs|MockInterface $sqs;
    private QueueConfig $queueConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sqs         = Mockery::mock(Sqs::class); // @phpstan-ignore-line
        $this->queueConfig = new QueueConfig('test', false);
    }

    public function testQueueAutoCreation(): void
    {
        $this->queueConfig->autoCreateQueue = true;

        $this->expectQueueExistenceChecked(false)
            ->expectQueueCreated();

        $this->getSut();
    }

    public function testGetMessage(): void
    {
        $message = $this->getMessage();

        $this->expectMessageRetrieved($message);

        $this->assertSame($message, $this->getSut()->getMessage());
    }

    public function testAbortMessage(): void
    {
        $this->getSut()->abortMessage($this->getMessage());

        // Noop assert, this test just checks that this method does nothing
        $this->assertTrue(true);
    }

    public function testFinishMessageWithInvalidMessage(): void
    {
        $this->expectException(RuntimeException::class);

        /** @var MessageInterface $message */
        $message = Mockery::mock(MessageInterface::class);

        $this->getSut()->finishMessage($message);
    }

    public function testFinishMessageWithValidMessage(): void
    {
        $message = $this->getMessage();

        $this->expectMessageReceiptHandleRetrieved($message, 'receiptHandle')
            ->expectMessageDeleted('receiptHandle');

        $this->getSut()->finishMessage($message);
    }

    public function testPublishMessageWithSuccess(): void
    {
        $message = $this->getMessage();

        $this->expectMessageSent($message);

        $this->getSut()->publishMessage($message);
    }

    public function testPublishMessageWithException(): void
    {
        $this->expectException(PublishingFailedException::class);

        $message = $this->getMessage();

        $this->expectMessageSentAndThrowException($message);

        $this->getSut()->publishMessage($message);
    }

    public function testPublishDelayedWithSuccess(): void
    {
        $message = $this->getMessage();

        $this->expectMessageSent($message, 5);

        $this->getSut()->publishDelayed($message, new \DateInterval('PT5S'));
    }

    public function testPublishDelayedWithException(): void
    {
        $this->expectException(PublishingFailedException::class);

        $message = $this->getMessage();

        $this->expectMessageSentAndThrowException($message, 5);

        $this->getSut()->publishDelayed($message, new \DateInterval('PT5S'));

    }

    private function getSut(): SqsQueue
    {
        return new SqsQueue($this->sqs, $this->queueConfig);
    }

    private function expectQueueExistenceChecked(bool $queueExists): static
    {
        $this->sqs->shouldReceive('checkQueueExists') //@phpstan-ignore-line
            ->once()
            ->with($this->queueConfig->queueName)
            ->andReturn($queueExists);

        return $this;
    }

    private function expectQueueCreated(): static
    {
        $this->sqs->shouldReceive('createQueue') //@phpstan-ignore-line
            ->once()
            ->with($this->queueConfig);

        return $this;
    }

    private function expectMessageRetrieved(Message $message): static
    {
        $this->sqs->shouldReceive('receiveMessage') // @phpstan-ignore-line
            ->with($this->queueConfig->queueName)
            ->andReturn($message);

        return $this;
    }

    private function expectMessageReceiptHandleRetrieved(Message|MockInterface $message, string $receiptHandle): static
    {
        $message->shouldReceive('getReceiptHandle') // @phpstan-ignore-line
            ->once()
            ->withNoArgs()
            ->andReturn($receiptHandle);

        return $this;
    }

    private function expectMessageDeleted(string $receiptHandle): static
    {
        $this->sqs->shouldReceive('deleteMessage') // @phpstan-ignore-line
            ->once()
            ->with($receiptHandle, $this->queueConfig->queueName);

        return $this;
    }

    private function expectMessageSent(Message $message, int $delaySeconds = 0): static
    {
        $this->sqs->shouldReceive('sendMessage') // @phpstan-ignore-line
            ->once()
            ->with($this->queueConfig->queueName, $message, $delaySeconds);

        return $this;
    }

    private function expectMessageSentAndThrowException(Message $message, int $delaySeconds = 0): static
    {
        $this->sqs->shouldReceive('sendMessage') // @phpstan-ignore-line
            ->once()
            ->with($this->queueConfig->queueName, $message, $delaySeconds)
            ->andThrow(new RuntimeException());

        return $this;
    }

    private function getMessage(): Message|MockInterface
    {
        return Mockery::mock(Message::class); // @phpstan-ignore-line
    }
}
