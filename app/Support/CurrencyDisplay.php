<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Central presentation helper for monetary values.
 *
 * IMPORTANT:
 *
 * - Database/payment values remain integer paise.
 * - Business logic must never depend on formatted currency strings.
 * - This class is presentation-only.
 * - Floating-point arithmetic is deliberately avoided.
 */
final class CurrencyDisplay
{
    /**
     * Format an integer paise value as Indian Rupees.
     *
     * Examples:
     *
     * 299900 => ₹2,999
     * 299950 => ₹2,999.50
     * 0      => ₹0
     */
    public static function formatIndianRupees(
        int $amountPaise
    ): string {
        $amountPaise =
            max(
                0,
                $amountPaise
            );

        $rupees =
            intdiv(
                $amountPaise,
                100
            );

        $paise =
            $amountPaise % 100;

        return '₹'
            . number_format(
                $rupees,
                0,
                '.',
                ','
            )
            . (
                $paise > 0
                ? '.'
                . str_pad(
                    (string) $paise,
                    2,
                    '0',
                    STR_PAD_LEFT
                )
                : ''
            );
    }
}
