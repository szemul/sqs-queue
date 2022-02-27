<?php
declare(strict_types=1);

namespace Szemul\SqsQueue\Config;

class QueueConfig
{
    public function __construct(
        public string $queueName,
        public bool $autoCreateQueue = true,
        public int $delaySeconds = 0,
        public int $maximumMessageSizeBytes = 262_144,
        public int $messageRetentionPeriodSeconds = 14 * 86_400,
        public int $receiveMessageWaitTimeSeconds = 0,
        public int $visibilityTimeoutSeconds = 30,
        public int $redriveMaxReceiveCount = 5,
        public ?string $redriveDeadLetterTargetArn = null,
    ) {
    }

    /**
     * @return array<string,int|string>
     */
    public function getSqsAttributes(): array
    {
        $attributes = [
            'DelaySeconds'                  => $this->delaySeconds,
            'MaximumMessageSize'            => $this->maximumMessageSizeBytes,
            'MessageRetentionPeriod'        => $this->messageRetentionPeriodSeconds,
            'ReceiveMessageWaitTimeSeconds' => $this->receiveMessageWaitTimeSeconds,
            'VisibilityTimeout'             => $this->visibilityTimeoutSeconds,
        ];

        if ($this->redriveDeadLetterTargetArn && $this->redriveMaxReceiveCount) {
            $attributes['RedrivePolicy'] = json_encode([
                'maxReceiveCount'     => $this->redriveMaxReceiveCount,
                'deadLetterTargetArn' => $this->redriveDeadLetterTargetArn,
            ]);
        }

        return $attributes;
    }
}
