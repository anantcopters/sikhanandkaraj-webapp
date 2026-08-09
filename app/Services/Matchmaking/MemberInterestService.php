<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\MemberInterestModel;
use App\Models\MemberMatchCandidateModel;
use App\Models\UserModel;
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

    public const DIRECTION_MUTUAL =
    'mutual';

    public function __construct(
        private readonly UserModel
        $userModel,

        private readonly MemberInterestModel
        $interestModel,

        private readonly MemberMatchCandidateModel
        $candidateModel,

        private readonly MemberPhotoUrlService
        $photoUrlService,

        private readonly BaseConnection
        $database
    ) {}

    /**
     * Build the complete Interest page dataset.
     *
     * Mutual records are deliberately removed from the ordinary
     * Received/Sent collections.
     *
     * A successfully matched profile should therefore appear in
     * exactly one product bucket: Mutual Interests.
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

        /*
     * Mutual Interests has no secondary status filter.
     */
        $filter =
            $direction
            === self::DIRECTION_MUTUAL
            ? self::FILTER_ALL
            : $this->normaliseFilter(
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
     * Apply current member visibility/authorization before
     * displaying or counting anything.
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

        /*
     * Every valid mutual relationship has two rows.
     *
     * Use the received-side row as the canonical member-facing
     * representation so the profile appears exactly once.
     */
        $mutualRecords =
            array_values(
                array_filter(
                    $receivedRecords,
                    fn(
                        array $record
                    ): bool =>
                    $this->recordStatus(
                        $record
                    )
                        === MemberInterestModel
                        ::STATUS_MUTUAL
                )
            );

        /*
     * Mutual profiles are promoted out of normal
     * Received/Sent activity.
     */
        $receivedRecords =
            $this->excludeMutual(
                $receivedRecords
            );

        $sentRecords =
            $this->excludeMutual(
                $sentRecords
            );

        $activeRecords =
            match ($direction) {
                self::DIRECTION_SENT =>
                $sentRecords,

                self::DIRECTION_MUTUAL =>
                $mutualRecords,

                default =>
                $receivedRecords,
            };

        $filteredRecords =
            $direction
            === self::DIRECTION_MUTUAL
            ? $activeRecords
            : $this->filterRecords(
                $activeRecords,
                $filter
            );

        return [
            'activeDirection' =>
            $direction,

            'activeFilter' =>
            $filter,

            'counts' => [
                'mutual' => [
                    'all' =>
                    count(
                        $mutualRecords
                    ),
                ],

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
     * Accept a pending received interest.
     *
     * @return bool TRUE when state changed.
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
     * Decline a pending received interest.
     *
     * @return bool TRUE when state changed.
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
     * @param list<array<string, mixed>> $records
     *
     * @return list<array<string, mixed>>
     */
    private function excludeMutual(
        array $records
    ): array {
        return array_values(
            array_filter(
                $records,
                fn(
                    array $record
                ): bool =>
                $this->recordStatus(
                    $record
                )
                    !== MemberInterestModel
                    ::STATUS_MUTUAL
            )
        );
    }


    /**
     * Resolve one Interest record's effective status.
     *
     * Historical NULL/blank values remain PENDING.
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
     * Change the status of an interest.
     *
     * Only the recipient is supplied as $toUserId by the
     * authenticated controller.
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
             * Lock the relationship before reading its status.
             *
             * This prevents simultaneous Accept/Decline
             * requests from racing each other.
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

            $interest = $this
                ->interestModel
                ->findBetween(
                    $fromUserId,
                    $toUserId
                );

            if (!is_array($interest)) {
                throw new DomainException(
                    'This interest is no longer available.'
                );
            }

            $currentStatus =
                strtoupper(
                    trim(
                        (string) (
                            $interest['status']
                            ?? ''
                        )
                    )
                );

            /*
             * Repeated browser submission of the same decision
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
             * An already-final response cannot be changed using
             * the normal member UI.
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

            if ($interestId <= 0) {
                throw new RuntimeException(
                    'The interest could not be resolved.'
                );
            }

            $updated = $this
                ->interestModel
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

            if ($updated === false) {
                throw new RuntimeException(
                    'The interest response could not be saved.'
                );
            }

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
        } catch (Throwable $exception) {
            $this->database
                ->transRollback();

            throw $exception;
        }
    }

    /**
     * Remove records whose related member is not currently
     * visible to the authenticated member.
     *
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
        if ($records === []) {
            return [];
        }

        $memberIds = [];

        foreach ($records as $record) {
            $memberId = $direction
                === self::DIRECTION_SENT
                ? (int) (
                    $record['to_user_id']
                    ?? 0
                )
                : (int) (
                    $record['from_user_id']
                    ?? 0
                );

            if ($memberId > 0) {
                $memberIds[] =
                    $memberId;
            }
        }

        if ($memberIds === []) {
            return [];
        }

        $visibleRows = $this
            ->candidateModel
            ->visibleCandidatesByIds(
                $viewerUserId,
                $viewerGender,
                $memberIds
            );

        $visibleMemberIds = [];

        foreach ($visibleRows as $row) {
            $visibleMemberIds[(int) (
                $row['id']
                ?? 0
            )] = $row;
        }

        $result = [];

        foreach ($records as $record) {
            $memberId = $direction
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
                    $visibleMemberIds[$memberId]
                )
            ) {
                continue;
            }

            $record['member'] =
                $visibleMemberIds[$memberId];

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

        foreach ($records as $record) {
            ++$counts['all'];

            /*
         * Historical interest rows may not have a status
         * populated yet.
         *
         * The Interest UI already treats such rows as
         * PENDING, therefore counts must use exactly the
         * same default so the navigation and profile card
         * cannot disagree.
         */
            $status = strtolower(
                trim(
                    (string) (
                        $record['status']
                        ?? MemberInterestModel
                        ::STATUS_PENDING
                    )
                )
            );

            if (
                $status === ''
            ) {
                $status =
                    strtolower(
                        MemberInterestModel
                        ::STATUS_PENDING
                    );
            }

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
                static function (
                    array $record
                ) use (
                    $requiredStatus
                ): bool {
                    /*
                 * Historical records may not have status populated.
                 *
                 * The Interest module treats such records as PENDING,
                 * therefore filtering must use the same rule as:
                 *
                 * - profile presentation;
                 * - left-side counts.
                 */
                    $status = strtoupper(
                        trim(
                            (string) (
                                $record['status']
                                ?? MemberInterestModel
                                ::STATUS_PENDING
                            )
                        )
                    );

                    /*
                 * Also handle a physically stored blank value.
                 */
                    if ($status === '') {
                        $status =
                            MemberInterestModel
                            ::STATUS_PENDING;
                    }

                    return $status
                        === $requiredStatus;
                }
            )
        );
    }

    /**
     * Convert database/candidate records into view-safe data.
     *
     * @param list<array<string, mixed>> $records
     *
     * @return list<array<string, mixed>>
     */
    private function presentationProfiles(
        int $viewerUserId,
        array $records,
        string $direction
    ): array {
        $result = [];

        foreach ($records as $record) {
            $member =
                $record['member']
                ?? null;

            if (!is_array($member)) {
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
             * Every row on this page already has an interest
             * relationship, so INTERESTED_MEMBERS photo
             * visibility is satisfied.
             *
             * Multi-profile listing rule:
             * thumbnail only.
             */
            $image = $this
                ->photoUrlService
                ->getApprovedPrimaryUrlForViewer(
                    memberId: $memberId,

                    viewerUserId: $viewerUserId,

                    hasInterestRelationship: true,

                    variant: 'thumbnail'
                );

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

                'mutualAt' =>
                $record['responded_at']
                    ?? null,

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
                self::DIRECTION_MUTUAL,
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
        $value = trim(
            (string) $dateOfBirth
        );

        if ($value === '') {
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

            if ($birthDate > $today) {
                return null;
            }

            return $birthDate
                ->diff(
                    $today
                )
                ->y;
        } catch (Throwable) {
            return null;
        }
    }
}
