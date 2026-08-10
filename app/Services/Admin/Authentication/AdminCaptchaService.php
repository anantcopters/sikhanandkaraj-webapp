<?php

declare(strict_types=1);

namespace App\Services\Admin\Authentication;

final class AdminCaptchaService
{
    private const SESSION_KEY =
    'admin_login_captcha';

    private const EXPIRY_SECONDS =
    300;

    /**
     * Generate a new administrator login CAPTCHA.
     *
     * Any previous challenge is replaced.
     */
    public function generate(): string
    {
        $operator = random_int(0, 1) === 0
            ? '+'
            : '-';

        if ($operator === '+') {
            $firstNumber = random_int(2, 15);
            $secondNumber = random_int(1, 10);

            $answer =
                $firstNumber
                + $secondNumber;
        } else {
            /*
             * Ensure subtraction never produces
             * a negative answer.
             */
            $firstNumber = random_int(5, 20);
            $secondNumber = random_int(
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
            self::SESSION_KEY,
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
     * Verify submitted CAPTCHA answer.
     *
     * CAPTCHA is always consumed after verification,
     * whether verification succeeds or fails.
     */
    public function verify(
        string $submittedAnswer
    ): bool {
        $submittedAnswer =
            trim($submittedAnswer);

        $captchaData = session()->get(
            self::SESSION_KEY
        );

        /*
         * CAPTCHA is single-use.
         *
         * Remove it before doing any checks so a
         * failed or successful answer cannot be replayed.
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

        /*
         * Reject expired CAPTCHA.
         */
        if (
            (time() - $createdAt)
            > self::EXPIRY_SECONDS
        ) {
            return false;
        }

        /*
         * Arithmetic CAPTCHA answers must be
         * non-negative integers only.
         */
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
     * Remove the current CAPTCHA from session.
     */
    public function clear(): void
    {
        session()->remove(
            self::SESSION_KEY
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
