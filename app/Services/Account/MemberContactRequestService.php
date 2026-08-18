<?php

declare(strict_types=1);

namespace App\Services\Account;

use App\Models\MemberContactRequestModel;
use DomainException;
use RuntimeException;

final class MemberContactRequestService
{
    private const MINIMUM_SUBMISSION_INTERVAL_SECONDS =
    300;

    private const MAXIMUM_REFERENCE_ATTEMPTS =
    10;

    public function __construct(
        private readonly MemberContactRequestModel
        $requestModel
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function historyForMember(
        int $memberUserId
    ): array {
        if ($memberUserId <= 0) {
            return [];
        }

        return $this
            ->requestModel
            ->historyForMember(
                $memberUserId
            );
    }

    /**
     * Create a support request and return its public reference.
     */
    public function create(
        int $memberUserId,
        string $message
    ): string {
        $message = preg_replace(
            '/\s+/u',
            ' ',
            trim($message)
        ) ?? '';

        if (
            mb_strlen($message) < 10
            || mb_strlen($message) > 255
        ) {
            throw new DomainException(
                'The message must contain between '
                    . '10 and 255 characters.'
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
                        . 'Please wait five minutes '
                        . 'before sending another.'
                );
            }
        }

        for (
            $attempt = 1;
            $attempt
                <= self::MAXIMUM_REFERENCE_ATTEMPTS;
            $attempt++
        ) {
            $requestReference =
                $this->generateReference();

            if (
                $this->requestModel
                ->referenceExists(
                    $requestReference
                )
            ) {
                continue;
            }

            $inserted = $this
                ->requestModel
                ->insert(
                    [
                        'request_reference' =>
                        $requestReference,

                        'member_user_id' =>
                        $memberUserId,

                        'message' =>
                        $message,

                        'status' =>
                        MemberContactRequestModel
                        ::STATUS_OPEN,
                    ],
                    true
                );

            if (is_numeric($inserted)) {
                return $requestReference;
            }

            /*
             * A concurrent request may have generated the same
             * reference after referenceExists(). Retry when the
             * database reports a unique-reference collision.
             */
            $databaseError =
                $this->requestModel
                ->errors();

            if ($databaseError !== []) {
                continue;
            }
        }

        throw new RuntimeException(
            'A unique support request reference '
                . 'could not be generated.'
        );
    }

    private function generateReference(): string
    {
        return 'SAKSUPP-'
            . (string) random_int(
                100000,
                999999
            );
    }
}
