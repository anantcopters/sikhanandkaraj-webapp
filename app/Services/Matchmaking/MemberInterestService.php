<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\MemberInterestModel;
use App\Models\MemberMatchCandidateModel;
use App\Models\MemberNotificationModel;
use App\Models\UserModel;
use App\Services\Notification\MemberNotificationService;
use App\Services\Profile\MemberPhotoUrlService;
use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Provides member Interest listing and response workflows.
 */
final class MemberInterestService
{
    public const DIRECTION_RECEIVED =
    'received';

    public const DIRECTION_SENT =
    'sent';

    public const FILTER_ALL =
    'all';

    public const FILTER_PENDING =
    'pending';

    public const FILTER_ACCEPTED =
    'accepted';

    public const FILTER_DECLINED =
    'declined';

    public function __construct(
        private readonly UserModel
        $userModel,

        private readonly MemberInterestModel
        $interestModel,

        private readonly MemberMatchCandidateModel
        $candidateModel,

        private readonly MemberPhotoUrlService
        $photoUrlService,

        private readonly MemberNotificationService
        $notificationService,

        private readonly BaseConnection
        $database
    ) {}

    /**
     * Build the Interest screen dataset.
     *
     * @return array<string, mixed>
     */
    public function pageData(
        int $userId,
        string $direction,
        string $filter
    ): array {
        $direction =
            $this->normaliseDirection(
                $direction
            );

        $filter =
            $this->normaliseFilter(
                $filter
            );

        $viewer =
            $this->userModel
            ->find(
                $userId
            );

        if (
            !is_array(
                $viewer
            )
        ) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $viewerGender =
            trim(
                (string) (
                    $viewer['gender']
                    ?? ''
                )
            );

        $receivedRecords =
            $this->interestModel
            ->receivedFor(
                $userId
            );

        $sentRecords =
            $this->interestModel
            ->sentFor(
                $userId
            );

        /*
         * Apply normal member visibility before counts
         * or presentation.
         */
        $receivedRecords =
            $this->visibleInterestRecords(
                viewerUserId: $userId,

                viewerGender: $viewerGender,

                records: $receivedRecords,

                direction: self::DIRECTION_RECEIVED
            );

        $sentRecords =
            $this->visibleInterestRecords(
                viewerUserId: $userId,

                viewerGender: $viewerGender,

                records: $sentRecords,

                direction: self::DIRECTION_SENT
            );

        $activeRecords =
            $direction
            === self::DIRECTION_SENT
            ? $sentRecords
            : $receivedRecords;

        $filteredRecords =
            $this->filterRecords(
                $activeRecords,
                $filter
            );

        return [
            'activeDirection' =>
            $direction,

            'activeFilter' =>
            $filter,

            'counts' => [
                'received' =>
                $this->counts(
                    $receivedRecords
                ),

                'sent' =>
                $this->counts(
                    $sentRecords
                ),
            ],

            'profiles' =>
            $this->presentationProfiles(
                viewerUserId: $userId,

                records: $filteredRecords,

                direction: $direction
            ),
        ];
    }

    /**
     * Accept a Pending received Interest.
     */
    public function accept(
        int $fromUserId,
        int $toUserId
    ): bool {
        return $this->respond(
            fromUserId: $fromUserId,

            toUserId: $toUserId,

            newStatus: MemberInterestModel
            ::STATUS_ACCEPTED
        );
    }

    /**
     * Decline a Pending received Interest.
     */
    public function decline(
        int $fromUserId,
        int $toUserId
    ): bool {
        return $this->respond(
            fromUserId: $fromUserId,

            toUserId: $toUserId,

            newStatus: MemberInterestModel
            ::STATUS_DECLINED
        );
    }

    /**
     * Change the status of one received Interest.
     *
     * fromUserId = original sender.
     * toUserId   = authenticated recipient.
     */
    private function respond(
        int $fromUserId,
        int $toUserId,
        string $newStatus
    ): bool {
        if (
            $fromUserId <= 0
            || $toUserId <= 0
            || $fromUserId === $toUserId
        ) {
            throw new DomainException(
                'The interest could not be resolved.'
            );
        }

        if (
            !in_array(
                $newStatus,
                [
                    MemberInterestModel
                    ::STATUS_ACCEPTED,

                    MemberInterestModel
                    ::STATUS_DECLINED,
                ],
                true
            )
        ) {
            throw new DomainException(
                'The requested interest response is invalid.'
            );
        }

        $this->database
            ->transBegin();

        try {
            /*
             * Lock the exact Interest row before reading it.
             */
            $this->database->query(
                'SELECT id '
                    . 'FROM member_interests '
                    . 'WHERE from_user_id = ? '
                    . 'AND to_user_id = ? '
                    . 'FOR UPDATE',
                [
                    $fromUserId,
                    $toUserId,
                ]
            );

            $interest =
                $this->interestModel
                ->findBetween(
                    $fromUserId,
                    $toUserId
                );

            if (
                !is_array(
                    $interest
                )
            ) {
                throw new DomainException(
                    'This interest is no longer available.'
                );
            }

            $currentStatus =
                $this->recordStatus(
                    $interest
                );

            /*
             * Repeated submission of the same decision
             * remains idempotent.
             */
            if (
                $currentStatus
                === $newStatus
            ) {
                $this->database
                    ->transCommit();

                return false;
            }

            /*
             * Accepted/Declined are final member-facing states.
             */
            if (
                $currentStatus
                !== MemberInterestModel
                ::STATUS_PENDING
            ) {
                throw new DomainException(
                    'This interest has already been responded to.'
                );
            }

            $interestId = max(
                0,
                (int) (
                    $interest['id']
                    ?? 0
                )
            );

            if (
                $interestId <= 0
            ) {
                throw new RuntimeException(
                    'The interest could not be resolved.'
                );
            }

            $updated =
                $this->interestModel
                ->update(
                    $interestId,
                    [
                        'status' =>
                        $newStatus,

                        'responded_at' =>
                        date(
                            'Y-m-d H:i:s'
                        ),
                    ]
                );

            if (
                $updated === false
            ) {
                throw new RuntimeException(
                    'The interest response could not be saved.'
                );
            }

            /*
             * Notify the original sender of the decision.
             *
             * Notification is part of the same DB transaction.
             */
            $this->createResponseNotification(
                senderUserId: $fromUserId,

                responderUserId: $toUserId,

                interestId: $interestId,

                status: $newStatus
            );

            if (
                $this->database
                ->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The interest transaction failed.'
                );
            }

            $this->database
                ->transCommit();

            return true;
        } catch (
            Throwable $exception
        ) {
            $this->database
                ->transRollback();

            throw $exception;
        }
    }

    /**
     * Notify the original sender when the recipient
     * accepts or declines.
     */
    private function createResponseNotification(
        int $senderUserId,
        int $responderUserId,
        int $interestId,
        string $status
    ): void {
        $responder =
            $this->userModel
            ->find(
                $responderUserId
            );

        if (
            !is_array(
                $responder
            )
        ) {
            throw new RuntimeException(
                'The responding member could not be resolved.'
            );
        }

        $responderName =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) (
                        $responder['full_name']
                        ?? ''
                    )
                )
            )
            ?? '';

        if (
            $responderName === ''
        ) {
            $responderName =
                'A member';
        }

        $profileReference =
            trim(
                (string) (
                    $responder['profile_ref_number']
                    ?? ''
                )
            );

        if (
            $profileReference === ''
        ) {
            throw new RuntimeException(
                'The responding member profile could not be resolved.'
            );
        }

        $accepted =
            $status
            === MemberInterestModel
            ::STATUS_ACCEPTED;

        $this->notificationService
            ->create(
                [
                    'recipientUserId' =>
                    $senderUserId,

                    'actorUserId' =>
                    $responderUserId,

                    'type' =>
                    $accepted
                        ? MemberNotificationModel
                        ::TYPE_INTEREST_ACCEPTED
                        : MemberNotificationModel
                        ::TYPE_INTEREST_REJECTED,

                    'title' =>
                    $accepted
                        ? 'Interest Accepted'
                        : 'Interest Declined',

                    'message' =>
                    $responderName
                        . (
                            $accepted
                            ? ' accepted your interest.'
                            : ' declined your interest.'
                        ),

                    'entityType' =>
                    'MEMBER_INTEREST',

                    'entityId' =>
                    $interestId,

                    'targetUrl' =>
                    '/members/'
                        . rawurlencode(
                            $profileReference
                        ),
                ]
            );
    }

    /**
     * Resolve effective Interest status.
     *
     * Historical NULL/blank values remain Pending.
     *
     * @param array<string, mixed> $record
     */
    private function recordStatus(
        array $record
    ): string {
        $status =
            strtoupper(
                trim(
                    (string) (
                        $record['status']
                        ?? ''
                    )
                )
            );

        return $status !== ''
            ? $status
            : MemberInterestModel
            ::STATUS_PENDING;
    }

    /**
     * @param list<array<string, mixed>> $records
     *
     * @return list<array<string, mixed>>
     */
    private function visibleInterestRecords(
        int $viewerUserId,
        string $viewerGender,
        array $records,
        string $direction
    ): array {
        if (
            $records === []
        ) {
            return [];
        }

        $memberIds = [];

        foreach (
            $records
            as $record
        ) {
            $memberId =
                $direction
                === self::DIRECTION_SENT
                ? (int) (
                    $record['to_user_id']
                    ?? 0
                )
                : (int) (
                    $record['from_user_id']
                    ?? 0
                );

            if (
                $memberId > 0
            ) {
                $memberIds[] =
                    $memberId;
            }
        }

        if (
            $memberIds === []
        ) {
            return [];
        }

        $visibleRows =
            $this->candidateModel
            ->visibleCandidatesByIds(
                $viewerUserId,
                $viewerGender,
                $memberIds
            );

        $visibleMembers = [];

        foreach (
            $visibleRows
            as $row
        ) {
            $visibleMembers[(int) (
                $row['id']
                ?? 0
            )] = $row;
        }

        $result = [];

        foreach (
            $records
            as $record
        ) {
            $memberId =
                $direction
                === self::DIRECTION_SENT
                ? (int) (
                    $record['to_user_id']
                    ?? 0
                )
                : (int) (
                    $record['from_user_id']
                    ?? 0
                );

            if (
                $memberId <= 0
                || !isset(
                    $visibleMembers[$memberId]
                )
            ) {
                continue;
            }

            $record['member'] =
                $visibleMembers[$memberId];

            $result[] =
                $record;
        }

        return $result;
    }

    /**
     * @param list<array<string, mixed>> $records
     *
     * @return array{
     *     all:int,
     *     pending:int,
     *     accepted:int,
     *     declined:int
     * }
     */
    private function counts(
        array $records
    ): array {
        $counts = [
            'all' => 0,
            'pending' => 0,
            'accepted' => 0,
            'declined' => 0,
        ];

        foreach (
            $records
            as $record
        ) {
            ++$counts['all'];

            $status =
                strtolower(
                    $this->recordStatus(
                        $record
                    )
                );

            if (
                array_key_exists(
                    $status,
                    $counts
                )
            ) {
                ++$counts[$status];
            }
        }

        return $counts;
    }

    /**
     * @param list<array<string, mixed>> $records
     *
     * @return list<array<string, mixed>>
     */
    private function filterRecords(
        array $records,
        string $filter
    ): array {
        if (
            $filter
            === self::FILTER_ALL
        ) {
            return $records;
        }

        $requiredStatus =
            strtoupper(
                $filter
            );

        return array_values(
            array_filter(
                $records,
                fn(
                    array $record
                ): bool =>
                $this->recordStatus(
                    $record
                )
                    === $requiredStatus
            )
        );
    }

    /**
     * @param list<array<string, mixed>> $records
     *
     * @return list<array<string, mixed>>
     */
    private function presentationProfiles(
        int $viewerUserId,
        array $records,
        string $direction
    ): array {
        helper(
            'member_profile'
        );
        $result = [];

        foreach (
            $records
            as $record
        ) {
            $member =
                $record['member']
                ?? null;

            if (
                !is_array(
                    $member
                )
            ) {
                continue;
            }

            $memberId = max(
                0,
                (int) (
                    $member['id']
                    ?? 0
                )
            );

            $profileReference =
                trim(
                    (string) (
                        $member['profile_ref_number']
                        ?? ''
                    )
                );

            if (
                $memberId <= 0
                || $profileReference === ''
            ) {
                continue;
            }

            /*
            * Interest already establishes a relationship between the
            * two members, therefore INTERESTED_MEMBERS photo visibility
            * is satisfied.
            *
            * The listing continues to request only the thumbnail variant.
            */
            $image =
                $this->photoUrlService
                ->getApprovedPrimaryUrlForViewer(
                    memberId: $memberId,

                    viewerUserId: $viewerUserId,

                    hasInterestRelationship: true,

                    variant: 'thumbnail'
                );

            /*
            * Use the common gender placeholder only after the normal
            * photo-authorization flow has completed.
            *
            * This deliberately does not reveal whether the member has
            * no photo or whether a real photo cannot currently be shown.
            */
            if ($image === '') {
                $image =
                    member_profile_placeholder(
                        $member['gender']
                            ?? null
                    );
            }

            $result[] = [
                'referenceId' =>
                $profileReference,

                'name' =>
                trim(
                    (string) (
                        $member['full_name']
                        ?? 'Member'
                    )
                ),

                'age' =>
                $this->age(
                    $member['date_of_birth']
                        ?? null
                ),

                'city' =>
                trim(
                    (string) (
                        $member['city_name']
                        ?? ''
                    )
                ),

                'image' =>
                $image,

                'status' =>
                $this->recordStatus(
                    $record
                ),

                'createdAt' =>
                $record['created_at']
                    ?? null,

                'respondedAt' =>
                $record['responded_at']
                    ?? null,

                'direction' =>
                $direction,

                'profileUrl' =>
                route_to(
                    'web.members.view',
                    $profileReference
                ),
            ];
        }

        return $result;
    }

    private function normaliseDirection(
        string $direction
    ): string {
        $direction =
            strtolower(
                trim(
                    $direction
                )
            );

        return in_array(
            $direction,
            [
                self::DIRECTION_RECEIVED,
                self::DIRECTION_SENT,
            ],
            true
        )
            ? $direction
            : self::DIRECTION_RECEIVED;
    }

    private function normaliseFilter(
        string $filter
    ): string {
        $filter =
            strtolower(
                trim(
                    $filter
                )
            );

        return in_array(
            $filter,
            [
                self::FILTER_ALL,
                self::FILTER_PENDING,
                self::FILTER_ACCEPTED,
                self::FILTER_DECLINED,
            ],
            true
        )
            ? $filter
            : self::FILTER_ALL;
    }

    private function age(
        mixed $dateOfBirth
    ): ?int {
        $value =
            trim(
                (string) $dateOfBirth
            );

        if (
            $value === ''
        ) {
            return null;
        }

        try {
            $birthDate =
                DateTimeImmutable
                ::createFromFormat(
                    '!Y-m-d',
                    mb_substr(
                        $value,
                        0,
                        10
                    )
                );

            if (
                !$birthDate
                    instanceof DateTimeImmutable
            ) {
                return null;
            }

            $today =
                new DateTimeImmutable(
                    'today'
                );

            if (
                $birthDate > $today
            ) {
                return null;
            }

            return $birthDate
                ->diff(
                    $today
                )
                ->y;
        } catch (
            Throwable) {
            return null;
        }
    }
}
