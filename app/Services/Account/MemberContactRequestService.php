<?php

declare(strict_types=1);

namespace App\Services\Account;

use App\Models\MemberContactRequestModel;
use DomainException;
use RuntimeException;

/**
 * Owns authenticated member Contact Us submissions.
 */
final class MemberContactRequestService
{
    private const MINIMUM_SUBMISSION_INTERVAL_SECONDS =
    300;

    public function __construct(
        private readonly MemberContactRequestModel
        $requestModel
    ) {}

    public function create(
        int $memberUserId,
        string $message
    ): void {
        $message = preg_replace(
            '/\s+/u',
            ' ',
            trim($message)
        ) ?? '';

        if (
            mb_strlen($message) < 10
            || mb_strlen($message) > 2000
        ) {
            throw new DomainException(
                'The message must contain between 10 and 2000 characters.'
            );
        }

        $latest = $this
            ->requestModel
            ->latestForMember(
                $memberUserId
            );

        if (is_array($latest)) {
            $createdAt = strtotime(
                (string) (
                    $latest['created_at']
                    ?? ''
                )
            );

            if (
                $createdAt !== false
                && (
                    time() - $createdAt
                ) < self::MINIMUM_SUBMISSION_INTERVAL_SECONDS
            ) {
                throw new DomainException(
                    'Your previous request was received. '
                        . 'Please wait five minutes before sending another.'
                );
            }
        }

        $inserted = $this
            ->requestModel
            ->insert(
                [
                    'member_user_id' =>
                    $memberUserId,

                    'message' =>
                    $message,

                    'status' =>
                    MemberContactRequestModel::STATUS_OPEN,
                ],
                true
            );

        if (!is_numeric($inserted)) {
            throw new RuntimeException(
                'Your request could not be saved.'
            );
        }
    }
}
