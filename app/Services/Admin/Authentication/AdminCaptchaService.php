<?php

declare(strict_types=1);

namespace App\Services\Admin\Authentication;

use InvalidArgumentException;

final class AdminCaptchaService
{
    private const DEFAULT_SESSION_KEY =
    'admin_login_captcha';

    private const EXPIRY_SECONDS =
    300;

    private readonly string $sessionKey;

    /**
     * The service remains backward compatible with Admin login.
     *
     * A different session key allows another authentication
     * flow to reuse the same CAPTCHA implementation without
     * sharing CAPTCHA state with the administrator portal.
     */
    public function __construct(
        string $sessionKey =
        self::DEFAULT_SESSION_KEY
    ) {
        $sessionKey = trim(
            $sessionKey
        );

        if ($sessionKey === '') {
            throw new InvalidArgumentException(
                'CAPTCHA session key cannot be empty.'
            );
        }

        $this->sessionKey =
            $sessionKey;
    }

    /**
     * Generate a new arithmetic CAPTCHA.
     *
     * Any previous challenge for this authentication
     * context is replaced.
     */
    public function generate(): string
    {
        $operator =
            random_int(0, 1) === 0
            ? '+'
            : '-';

        if ($operator === '+') {
            $firstNumber =
                random_int(
                    2,
                    15
                );

            $secondNumber =
                random_int(
                    1,
                    10
                );

            $answer =
                $firstNumber
                + $secondNumber;
        } else {
            /*
             * Never generate a negative result.
             */
            $firstNumber =
                random_int(
                    5,
                    20
                );

            $secondNumber =
                random_int(
                    1,
                    $firstNumber
                );

            $answer =
                $firstNumber
                - $secondNumber;
        }

        $challenge =
            $firstNumber
            . ' '
            . $operator
            . ' '
            . $secondNumber;

        session()->set(
            $this->sessionKey,
            [
                'answer_hash' =>
                $this->hashAnswer(
                    (string) $answer
                ),

                'created_at' =>
                time(),
            ]
        );

        return $challenge;
    }

    /**
     * Verify the submitted CAPTCHA answer.
     *
     * CAPTCHA is single-use whether verification succeeds
     * or fails.
     */
    public function verify(
        string $submittedAnswer
    ): bool {
        $submittedAnswer =
            trim(
                $submittedAnswer
            );

        $captchaData =
            session()->get(
                $this->sessionKey
            );

        /*
         * Consume before checking so the challenge cannot
         * be replayed.
         */
        $this->clear();

        if (
            !is_array($captchaData)
            || $submittedAnswer === ''
        ) {
            return false;
        }

        $answerHash = trim(
            (string) (
                $captchaData['answer_hash']
                ?? ''
            )
        );

        $createdAt = (int) (
            $captchaData['created_at']
            ?? 0
        );

        if (
            $answerHash === ''
            || $createdAt <= 0
        ) {
            return false;
        }

        if (
            (time() - $createdAt)
            > self::EXPIRY_SECONDS
        ) {
            return false;
        }

        if (
            preg_match(
                '/^[0-9]{1,2}$/',
                $submittedAnswer
            ) !== 1
        ) {
            return false;
        }

        return hash_equals(
            $answerHash,
            $this->hashAnswer(
                $submittedAnswer
            )
        );
    }

    /**
     * Remove only this authentication context's CAPTCHA.
     */
    public function clear(): void
    {
        session()->remove(
            $this->sessionKey
        );
    }

    private function hashAnswer(
        string $answer
    ): string {
        return hash(
            'sha256',
            $answer
        );
    }
}
