<?php

declare(strict_types=1);

namespace App\Services\Account;

use App\Models\MemberContactRequestModel;
use App\Services\Email\MemberEmailService;
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
        $requestModel,

        /*
         * External email is a downstream communication channel.
         *
         * MemberEmailService is already failure-safe and returns null when
         * no verified primary email exists or the queue cannot be written.
         */
        private readonly MemberEmailService
        $memberEmailService
    ) {}

    /**
     * Return complete member-visible support history.
     *
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
     *
     * IMPORTANT:
     *
     * The support request is authoritative. Email is optional.
     * Therefore the database insert completes before the email is queued.
     */
    public function create(
        int $memberUserId,
        string $message
    ): string {
        if ($memberUserId <= 0) {
            throw new DomainException(
                'A valid member account is required.'
            );
        }

        $message =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $message
                )
            ) ?? '';

        if (
            mb_strlen(
                $message
            ) < 10
            || mb_strlen(
                $message
            ) > 255
        ) {
            throw new DomainException(
                'The message must contain between '
                    . '10 and 255 characters.'
            );
        }

        $latest =
            $this
            ->requestModel
            ->latestForMember(
                $memberUserId
            );

        if (is_array($latest)) {
            $createdAt =
                strtotime(
                    (string) (
                        $latest['created_at']
                        ?? ''
                    )
                );

            if (
                $createdAt !== false
                && (
                    time()
                    - $createdAt
                )
                < self::MINIMUM_SUBMISSION_INTERVAL_SECONDS
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
                $this
                ->requestModel
                ->referenceExists(
                    $requestReference
                )
            ) {
                continue;
            }

            $inserted =
                $this
                ->requestModel
                ->insert(
                    [
                        'request_reference' =>
                        $requestReference,

                        'member_user_id' =>
                        $memberUserId,

                        /*
                         * The original support message remains in the
                         * authenticated application. It is deliberately
                         * not copied into the acknowledgement email.
                         */
                        'message' =>
                        $message,

                        'status' =>
                        MemberContactRequestModel
                        ::STATUS_OPEN,
                    ],
                    true
                );

            if (is_numeric($inserted)) {
                $requestId =
                    (int) $inserted;

                /*
                 * Queue only after the support request exists.
                 *
                 * MemberEmailService catches queue/recipient failures,
                 * therefore an email problem cannot undo Contact Us.
                 */
                $this
                    ->memberEmailService
                    ->queueSupportRequestReceived(
                        recipientUserId: $memberUserId,

                        requestReference: $requestReference,

                        requestId: $requestId
                    );

                return $requestReference;
            }

            /*
             * A concurrent request may have generated the same reference
             * after referenceExists(). Retry when persistence reports a
             * collision/error.
             */
            $databaseError =
                $this
                ->requestModel
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

    /**
     * Generate the public support reference already used throughout the
     * existing Account Settings and Admin support UI.
     */
    private function generateReference(): string
    {
        return 'SAKSUPP-'
            . (string) random_int(
                100000,
                999999
            );
    }
}
