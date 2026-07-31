<?php

declare(strict_types=1);

namespace App\Services\Email;

use CodeIgniter\Email\Email;
use RuntimeException;
use Throwable;

final class MailService
{
    private Email $email;

    private string $lastDebugOutput = '';

    public function __construct(
        ?Email $email = null
    ) {
        $this->email = $email ?? service('email');
    }

    /**
     * Perform the actual SMTP delivery.
     *
     * @param array<string, mixed> $viewData
     */
    public function sendTemplate(
        string $recipientEmail,
        string $recipientName,
        string $subject,
        string $viewName,
        array $viewData = []
    ): void {
        $this->lastDebugOutput = '';

        $config = config('Email');

        if (
            trim($config->fromEmail) === ''
            || !filter_var(
                $config->fromEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new RuntimeException(
                'The sender email address is not configured.'
            );
        }

        $this->email->clear(true);

        $this->email->setFrom(
            $config->fromEmail,
            $config->fromName ?: 'SikhAnandKaraj'
        );

        $this->email->setTo(
            $recipientEmail,
            $recipientName
        );

        $this->email->setSubject($subject);

        $this->email->setMessage(
            view($viewName, $viewData)
        );

        try {
            /*
             * false preserves SMTP diagnostics for printDebugger().
             */
            if (!$this->email->send(false)) {
                $this->lastDebugOutput =
                    $this->email->printDebugger([
                        'headers',
                        'subject',
                    ]);

                throw new RuntimeException(
                    'SMTP rejected or failed to deliver the email.'
                );
            }
        } catch (Throwable $exception) {
            if ($this->lastDebugOutput === '') {
                $this->lastDebugOutput =
                    $this->email->printDebugger([
                        'headers',
                        'subject',
                    ]);
            }

            throw new RuntimeException(
                'Email could not be sent: '
                    . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    public function getLastDebugOutput(): string
    {
        return mb_substr(
            strip_tags($this->lastDebugOutput),
            0,
            10000
        );
    }
}
