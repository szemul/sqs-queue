<?php
declare(strict_types=1);

namespace Szemul\SqsQueue\Exception;

use Aws\Sqs\Exception\SqsException;
use Exception;
use JetBrains\PhpStorm\Pure;

/** @codeCoverageIgnore */
class QueueNotFoundException extends Exception
{
    #[Pure]
    public function __construct(string $queueName, SqsException $previous)
    {
        parent::__construct('SQS Queue not found: ' . $queueName, 0, $previous);
    }
}
