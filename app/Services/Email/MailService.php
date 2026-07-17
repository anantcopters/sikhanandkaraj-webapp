<?php

declare(strict_types=1);

namespace App\Services\Email;

use CodeIgniter\Email\Email;
use RuntimeException;
use Throwable;

final class MailService
{
    private Email $email;

    public function __construct(
        ?Email $email = null
    ) {
        $this->email = $email ?? service('email');
    }

    /**
     * Send an HTML email using a reusable CI4 view.
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
        $this->email->clear(true);

        $this->email->setTo(
            $recipientEmail,
            $recipientName
        );

        $this->email->setSubject($subject);

        $this->email->setMessage(
            view($viewName, $viewData)
        );

        try {
            if (!$this->email->send()) {
                log_message(
                    'error',
                    'Email could not be sent to {email}. Debug: {debug}',
                    [
                        'email' => $recipientEmail,
                        'debug' => $this->email->printDebugger([
                            'headers',
                        ]),
                    ]
                );

                throw new RuntimeException(
                    'Email could not be sent.'
                );
            }
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Email sending exception for {email}: {message}',
                [
                    'email' => $recipientEmail,
                    'message' => $exception->getMessage(),
                ]
            );

            throw new RuntimeException(
                'Email could not be sent.',
                0,
                $exception
            );
        }
    }
}
