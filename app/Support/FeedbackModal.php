<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Creates reusable flash messages for the application feedback modal.
 */
final class FeedbackModal
{
    public static function info(
        string $title,
        string $message,
        string $buttonText = 'Okay'
    ): void {
        self::set(
            'info',
            $title,
            $message,
            $buttonText
        );
    }

    public static function success(
        string $title,
        string $message,
        string $buttonText = 'Okay'
    ): void {
        self::set(
            'success',
            $title,
            $message,
            $buttonText
        );
    }

    public static function warning(
        string $title,
        string $message,
        string $buttonText = 'Okay'
    ): void {
        self::set(
            'warning',
            $title,
            $message,
            $buttonText
        );
    }

    public static function error(
        string $title,
        string $message,
        string $buttonText = 'Okay'
    ): void {
        self::set(
            'error',
            $title,
            $message,
            $buttonText
        );
    }

    private static function set(
        string $type,
        string $title,
        string $message,
        string $buttonText
    ): void {
        session()->setFlashdata('feedback_modal', [
            'type' => $type,
            'title' => trim($title),
            'message' => trim($message),
            'button_text' => trim($buttonText),
        ]);
    }

    private function __construct() {}
}
