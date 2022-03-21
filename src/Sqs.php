<?php
declare(strict_types=1);

namespace Szemul\SqsQueue;

use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use Szemul\Queue\Message\MessageInterface;
use Szemul\SqsQueue\Config\QueueConfig;
use Szemul\SqsQueue\Exception\QueueNotFoundException;
use Szemul\SqsQueue\Message\Message;

class Sqs
{
    public const ATTRIBUTE_KEY_ORIGINAL_QUEUE_NAME = 'original_queue_name';

    public const BODY_KEY_JOB_NAME = 'jobName';
    public const BODY_KEY_PAYLOAD  = 'payload';

    /** @var array<string, string> */
    protected array $queueUrlCache = [];

    public function __construct(protected SqsClient $sqsClient)
    {
    }

    /** @return array<string,mixed> */
    public function __debugInfo(): array
    {
        return [
            'sqsClient' => '** Instance of ' . get_class($this->sqsClient),
        ];
    }

    /**
     * @param array<string,string> $attributes
     * @throws QueueNotFoundException
     * @throws SqsException
     */
    public function sendMessage(
        string $queueName,
        MessageInterface $message,
        int $delaySeconds = 0,
        array $attributes = [],
    ): void {
        $attributes[self::ATTRIBUTE_KEY_ORIGINAL_QUEUE_NAME] = $queueName;

        $messageAttributes = [];

        foreach ($attributes as $key => $value) {
            $messageAttributes[$key] = [
                'StringValue' => $value,
                'DataType'    => 'String',
            ];
        }

        $response = $this->sqsClient->sendMessage([
            'QueueUrl'          => $this->getQueueUrl($queueName),
            'MessageBody'       => json_encode([
                self::BODY_KEY_JOB_NAME => $message->getJobName(),
                self::BODY_KEY_PAYLOAD  => $message->getPayload(),
            ]),
            'DelaySeconds'      => $delaySeconds,
            'MessageAttributes' => $messageAttributes,
        ]);

        $message->setQueueIdentifier((string)$response->get('MessageId'));
    }

    /**
     * @throws QueueNotFoundException
     * @throws SqsException
     */
    public function receiveMessage(string $queueName): ?MessageInterface
    {
        $args     = [
            'QueueUrl'              => $this->getQueueUrl($queueName),
            'WaitTimeSeconds'       => 20,
            'MaxNumberOfMessages'   => 1,
            'AttributeNames'        => [],
            'MessageAttributeNames' => ['All'],
        ];

        $response = $this->sqsClient->receiveMessage($args);

        $message = $response['Messages'][0] ?? null;

        if (null === $message) {
            return null;
        }

        $body = json_decode($message['Body'], true);

        return new Message(
            $body[self::BODY_KEY_JOB_NAME],
            $body[self::BODY_KEY_PAYLOAD],
            $message['MessageId'],
            $message['ReceiptHandle'],
            $message['Attributes'] ?? [],
            $message['MessageAttributes'] ?? [],
        );
    }

    /**
     * @throws QueueNotFoundException
     * @throws SqsException
     */
    public function deleteMessage(string $receiptHandle, string $queueName): void
    {
        $queueUrl = $this->getQueueUrl($queueName);

        $this->sqsClient->deleteMessage([
            'QueueUrl'      => $queueUrl,
            'ReceiptHandle' => $receiptHandle,
        ]);
    }

    /**
     * @throws SqsException
     */
    public function createQueue(QueueConfig $queueConfig): void
    {
        $response = $this->sqsClient->createQueue([
            'QueueName'  => $queueConfig->queueName,
            'Attributes' => $queueConfig->getSqsAttributes(),
        ]);

        $this->queueUrlCache[$queueConfig->queueName] = (string)$response->get('QueueUrl');
    }

    /**
     * @throws QueueNotFoundException
     * @throws SqsException
     */
    public function getQueueUrl(string $queueName): string
    {
        if (isset($this->queueUrlCache[$queueName])) {
            return $this->queueUrlCache[$queueName];
        }

        try {
            $response = $this->sqsClient->getQueueUrl([
                'QueueName' => $queueName,
            ]);
        } catch (SqsException $e) {
            if ('AWS.SimpleQueueService.NonExistentQueue' != $e->getAwsErrorCode()) {
                // This exception is not for a non existing queue, re-throw it
                throw $e;
            }

            throw new QueueNotFoundException($queueName, $e);
        }

        $queueUrl = (string)$response->get('QueueUrl');

        $this->queueUrlCache[$queueName] = $queueUrl;

        return $queueUrl;
    }

    /**
     * @throws SqsException
     */
    public function checkQueueExists(string $queueName): bool
    {
        try {
            $this->getQueueUrl($queueName);
        } catch (QueueNotFoundException) {
            return false;
        }

        return true;
    }
}
