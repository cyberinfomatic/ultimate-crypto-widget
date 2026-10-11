<?php

/**
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Adapters\Log;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Log\LogLevel;

/**
 * Keeps records in memory — for tests, and for collecting what happened during one operation.
 */
final class ArrayLogger extends BaseLogger
{
    /** @var list<array{level: LogLevel, message: string, context: array<string, mixed>}> */
    public array $records = [];

    protected function write(LogLevel $level, string $message, array $context): void
    {
        $this->records[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }

    /**
     * @return list<string> Messages only, oldest first.
     */
    public function messages(): array
    {
        return array_map(static fn (array $record): string => $record['message'], $this->records);
    }
}
