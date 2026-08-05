<?php

declare(strict_types=1);

namespace App\Services\Logging;

use App\Logging\ApplicationErrorLogWriter;
use Throwable;

/**
 * Explicit structured application-error logger.
 *
 * Use only when a catch block has useful structured context that would be
 * difficult to preserve in a rendered log message.
 */
final class ApplicationErrorLogger
{
    public function __construct(
        private readonly ApplicationErrorLogWriter $writer
    ) {}

    /**
     * Record a caught exception without throwing to the caller.
     *
     * @param array<string, mixed> $context
     */
    public function exception(
        Throwable $exception,
        string $severity = 'error',
        array $context = []
    ): void {
        $this->writer->write(
            severity: $severity,

            message: $exception->getMessage(),

            context: array_merge(
                $context,
                [
                    'exception' => [
                        'class' =>
                        $exception::class,

                        'file' =>
                        $exception->getFile(),

                        'line' =>
                        $exception->getLine(),

                        /*
                             * The full stack trace is intentionally omitted
                             * from the table initially. Add it only if there is
                             * a demonstrated diagnostic need.
                             */
                    ],
                ]
            )
        );
    }

    /**
     * Record a structured error without an exception object.
     *
     * @param array<string, mixed> $context
     */
    public function error(
        string $message,
        array $context = [],
        string $severity = 'error'
    ): void {
        $this->writer->write(
            severity: $severity,

            message: $message,

            context: $context
        );
    }
}
