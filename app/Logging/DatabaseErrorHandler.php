<?php

declare(strict_types=1);

namespace App\Logging;

use CodeIgniter\Log\Handlers\BaseHandler;
use Throwable;

/**
 * CodeIgniter log handler that writes warning-and-higher events to PostgreSQL.
 *
 * It must never interrupt the application or stop subsequent handlers.
 */
final class DatabaseErrorHandler extends BaseHandler
{
    /**
     * Prevent recursive writes in the current PHP process.
     */
    private static bool $isWriting = false;

    private ?ApplicationErrorLogWriter $writer = null;

    /**
     * Handle one rendered CodeIgniter log message.
     *
     * Returning true allows FileHandler and any later handlers to run.
     */
    public function handle(
        $level,
        $message
    ): bool {
        if (self::$isWriting) {
            return true;
        }

        self::$isWriting = true;

        try {
            $this->writer()
                ->write(
                    severity: (string) $level,

                    message: (string) $message
                );
        } catch (Throwable $exception) {
            /*
             * This is an additional protection around the writer. Never call
             * log_message() from inside a logging handler.
             */
            error_log(
                sprintf(
                    'DatabaseErrorHandler failed: %s',
                    $exception->getMessage()
                )
            );
        } finally {
            self::$isWriting = false;
        }

        return true;
    }

    private function writer(): ApplicationErrorLogWriter
    {
        if (
            $this->writer
            instanceof ApplicationErrorLogWriter
        ) {
            return $this->writer;
        }

        $this->writer =
            new ApplicationErrorLogWriter();

        return $this->writer;
    }
}
