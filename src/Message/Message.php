<?php
declare(strict_types=1);

namespace Szemul\SqsQueue\Message;

class Message extends \Szemul\Queue\Message\Message
{
    /**
     * @param mixed[]    $payload
     * @param string[][] $attributes
     * @param string[][] $messageAttributes
     */
    public function __construct(
        string $jobName,
        array $payload,
        ?string $queueIdentifier = null,
        protected ?string $receiptHandle = null,
        protected array $attributes = [],
        protected array $messageAttributes = [],
    ) {
        parent::__construct($jobName, $payload, $queueIdentifier);
    }

    public function getReceiptHandle(): ?string
    {
        return $this->receiptHandle;
    }

    /**
     * @return string[][]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return string[][]
     */
    public function getMessageAttributes(): array
    {
        return $this->messageAttributes;
    }
}
