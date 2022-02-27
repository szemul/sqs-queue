<?php
declare(strict_types=1);

namespace Szemul\SqsQueue\Queue;

use DateInterval;
use RuntimeException;
use Szemul\Queue\Exception\PublishingFailedException;
use Szemul\Queue\Message\MessageInterface;
use Szemul\Queue\Queue\ConsumerInterface;
use Szemul\Queue\Queue\DelayCapablePublisherInterface;
use Szemul\SqsQueue\Config\QueueConfig;
use Szemul\SqsQueue\Message\Message;
use Szemul\SqsQueue\Sqs;

class SqsQueue implements ConsumerInterface, DelayCapablePublisherInterface
{
    public function __construct(protected Sqs $sqs, protected QueueConfig $queueConfig)
    {
        if ($this->queueConfig->autoCreateQueue && !$this->sqs->checkQueueExists($this->queueConfig->queueName)) {
            $this->sqs->createQueue($this->queueConfig);
        }
    }

    /**
     * @throws \Szemul\SqsQueue\Exception\QueueNotFoundException
     */
    public function getMessage(): ?MessageInterface
    {
        return $this->sqs->receiveMessage($this->queueConfig->queueName);
    }

    public function abortMessage(MessageInterface $message): void
    {
        // Noop - doesn't make sense in SQS, the message will reach its timeout to abort
    }

    public function finishMessage(MessageInterface $message): void
    {
        if ($message instanceof Message) {
            $this->sqs->deleteMessage($message->getReceiptHandle(), $this->queueConfig->queueName);
        }

        throw new RuntimeException(
            'Unable to mark a message as finished, as only instance of ' . Message::class
                . ' is supported, received ' . get_class($message),
        );
    }

    public function publishMessage(MessageInterface $message): void
    {
        try {
            $this->sqs->sendMessage($this->queueConfig->queueName, $message);
        } catch (\Throwable $e) {
            throw new PublishingFailedException($message, $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function publishDelayed(MessageInterface $message, DateInterval $delay): void
    {
        try {
            $this->sqs->sendMessage($this->queueConfig->queueName, $message, $delay->s);
        } catch (\Throwable $e) {
            throw new PublishingFailedException($message, $e->getMessage(), $e->getCode(), $e);
        }
    }
}
