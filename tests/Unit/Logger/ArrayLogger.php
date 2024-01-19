<?php

declare(strict_types=1);

namespace App\Tests\Unit\Logger;

use Psr\Log\AbstractLogger;

class ArrayLogger extends AbstractLogger
{
    private array $logs = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }

    public function getLogs(?int $index = null): array
    {
        return $index !== null ? ($this->logs[$index] ?? []) : $this->logs;
    }
}
