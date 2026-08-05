<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Creates safe diagnostic context for authentication-related errors.
 *
 * Security rules:
 * - passwords are never included;
 * - OTP values are never included;
 * - complete email addresses are never included;
 * - complete mobile numbers are never included;
 * - the helper does not start a session exclusively for logging.
 */
final class AuthenticationErrorContext
{
    /**
     * Add the authenticated member identifier when it already exists.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public static function withAuthenticatedMember(
        array $context = []
    ): array {
        if (
            !function_exists('session')
            || session_status() !== PHP_SESSION_ACTIVE
        ) {
            return $context;
        }

        $memberUserId = session(
            'auth_user_id'
        );

        if (
            is_numeric($memberUserId)
            && (int) $memberUserId > 0
        ) {
            $context['member_user_id'] =
                (int) $memberUserId;
        }

        return $context;
    }

    /**
     * Determine whether a login identifier is an email address or mobile.
     *
     * The actual submitted value is deliberately not returned.
     */
    public static function identifierType(
        string $identifier
    ): string {
        $normalizedIdentifier = trim(
            $identifier
        );

        if (
            filter_var(
                $normalizedIdentifier,
                FILTER_VALIDATE_EMAIL
            ) !== false
        ) {
            return 'EMAIL';
        }

        return 'MOBILE';
    }

    /**
     * Return only the final four digits of a mobile number.
     *
     * Use this only when limited mobile correlation is diagnostically useful.
     */
    public static function mobileSuffix(
        string $mobileNumber
    ): string {
        $digits = preg_replace(
            '/\D+/',
            '',
            $mobileNumber
        ) ?? '';

        if ($digits === '') {
            return '';
        }

        return mb_substr(
            $digits,
            -4
        );
    }

    private function __construct() {}
}
