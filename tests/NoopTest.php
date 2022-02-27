<?php
declare(strict_types=1);

namespace Szemul\SqsQueue\Test;

use PHPUnit\Framework\TestCase;

class NoopTest extends TestCase
{
    public function testNoop(): void
    {
        $this->assertTrue(true);
    }
}
