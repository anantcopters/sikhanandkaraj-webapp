<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\MemberBlockModel;
use App\Models\MemberInterestModel;
use App\Models\MemberProfileViewModel;
use App\Models\UserModel;
use App\Models\MemberShortlistModel;
use App\Services\Notification\MemberNotificationService;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Owns member-to-member interaction state changes.
 */
final class MemberInteractionService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly MemberBlockModel $blockModel,
        private readonly MemberInterestModel $interestModel,
        private readonly MemberShortlistModel $shortlistModel,
        private readonly MemberProfileViewModel $profileViewModel,
        private readonly MemberNotificationService $notificationService,
        private readonly BaseConnection $database
    ) {}

    /**
     * Show interest in another active and visible member.
     *
     * A reciprocal positive interest automatically promotes
     * both directional records to MUTUAL.
     *
     * Examples:
     *
     * A -> B = PENDING
     * B -> A newly created
     *
     * becomes:
     *
     * A -> B = MUTUAL
     * B -> A = MUTUAL
     *
     * A reverse DECLINED interest does not create a mutual match.
     *
     * @return bool TRUE when a new directional interest was created,
     *              FALSE when it already existed.
     */
    public function showInterest(
        int $fromUserId,
        int $toUserId
    ): bool {
        $this->assertVisiblePair(
            $fromUserId,
            $toUserId
        );

        /*
     * Fast-path for normal duplicate submissions.
     */
        if (
            $this->interestModel
            ->hasShown(
                $fromUserId,
                $toUserId
            )
        ) {
            return false;
        }

        $this->database
            ->transBegin();

        try {
            /*
         * Lock both members in deterministic order.
         *
         * Reciprocal interest creation must be serialized
         * so two simultaneous requests cannot create an
         * inconsistent relationship state.
         */
            $this->database->query(
                'SELECT id '
                    . 'FROM users '
                    . 'WHERE id IN (?, ?) '
                    . 'ORDER BY id '
                    . 'FOR UPDATE',
                [
                    $fromUserId,
                    $toUserId,
                ]
            );

            /*
         * Recheck after obtaining the lock.
         */
            if (
                $this->interestModel
                ->hasShown(
                    $fromUserId,
                    $toUserId
                )
            ) {
                $this->database
                    ->transCommit();

                return false;
            }

            /*
         * Look for an existing interest in the opposite
         * direction.
         */
            $reverseInterest =
                $this->interestModel
                ->findBetween(
                    $toUserId,
                    $fromUserId
                );

            $reverseStatus = '';

            if (
                is_array(
                    $reverseInterest
                )
            ) {
                $reverseStatus =
                    $this->normaliseInterestStatus(
                        $reverseInterest['status']
                            ?? null
                    );
            }

            /*
         * Reciprocal PENDING or ACCEPTED interest represents
         * positive intent from both members.
         *
         * MUTUAL is included defensively for historical/
         * repaired data.
         *
         * DECLINED deliberately does not qualify.
         */
            $becomesMutual =
                in_array(
                    $reverseStatus,
                    [
                        MemberInterestModel
                        ::STATUS_PENDING,

                        MemberInterestModel
                        ::STATUS_ACCEPTED,

                        MemberInterestModel
                        ::STATUS_MUTUAL,
                    ],
                    true
                );

            $now =
                date(
                    'Y-m-d H:i:s'
                );

            $newInterestData = [
                'from_user_id' =>
                $fromUserId,

                'to_user_id' =>
                $toUserId,

                /*
             * Set this explicitly.
             *
             * Do not rely solely on the PostgreSQL default
             * for important relationship state.
             */
                'status' =>
                $becomesMutual
                    ? MemberInterestModel
                    ::STATUS_MUTUAL
                    : MemberInterestModel
                    ::STATUS_PENDING,

                'responded_at' =>
                $becomesMutual
                    ? $now
                    : null,
            ];

            $insertId = $this
                ->interestModel
                ->insert(
                    $newInterestData,
                    true
                );

            if (
                !is_numeric(
                    $insertId
                )
                || (int) $insertId <= 0
            ) {
                throw new RuntimeException(
                    'The interest could not be saved.'
                );
            }

            if ($becomesMutual) {
                if (
                    !is_array(
                        $reverseInterest
                    )
                ) {
                    throw new RuntimeException(
                        'The reciprocal interest could not be resolved.'
                    );
                }

                $reverseInterestId = max(
                    0,
                    (int) (
                        $reverseInterest['id']
                        ?? 0
                    )
                );

                if (
                    $reverseInterestId <= 0
                ) {
                    throw new RuntimeException(
                        'The reciprocal interest could not be resolved.'
                    );
                }

                /*
             * Promote the original directional record as part
             * of the exact same transaction.
             */
                $updated = $this
                    ->interestModel
                    ->update(
                        $reverseInterestId,
                        [
                            'status' =>
                            MemberInterestModel
                            ::STATUS_MUTUAL,

                            'responded_at' =>
                            $now,
                        ]
                    );

                if (
                    $updated === false
                ) {
                    throw new RuntimeException(
                        'The reciprocal interest could not be updated.'
                    );
                }

                /*
             * Both members receive a Mutual Interest
             * notification rather than creating an ordinary
             * "New Interest" notification.
             */
                $this->createMutualInterestNotifications(
                    firstUserId: $fromUserId,

                    secondUserId: $toUserId,

                    interestId: (int) $insertId
                );
            } else {
                /*
             * Ordinary one-way Interest notification.
             */
                $this->createInterestReceivedNotification(
                    fromUserId: $fromUserId,

                    toUserId: $toUserId,

                    interestId: (int) $insertId
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
        } catch (
            Throwable $exception
        ) {
            $this->database
                ->transRollback();

            throw $exception;
        }
    }

    /**
     * Add or remove another member from the authenticated
     * member's shortlist.
     *
     * @return bool TRUE when shortlisted after the operation,
     *              FALSE when removed.
     */
    public function toggleShortlist(
        int $userId,
        int $shortlistedUserId
    ): bool {
        /*
     * Reuse exactly the same member-pair authorization used
     * by Interest and profile views.
     *
     * This prevents:
     *
     * - self-shortlisting;
     * - inactive accounts;
     * - blocked member relationships.
     */
        $this->assertVisiblePair(
            $userId,
            $shortlistedUserId
        );

        if (
            $this->shortlistModel
            ->hasShortlisted(
                $userId,
                $shortlistedUserId
            )
        ) {
            $removed = $this
                ->shortlistModel
                ->removeShortlist(
                    $userId,
                    $shortlistedUserId
                );

            if ($removed === false) {
                throw new RuntimeException(
                    'The shortlist could not be updated.'
                );
            }

            return false;
        }

        try {
            $insertId = $this
                ->shortlistModel
                ->insert(
                    [
                        'user_id' =>
                        $userId,

                        'shortlisted_user_id' =>
                        $shortlistedUserId,
                    ],
                    true
                );

            if (!is_numeric($insertId)) {
                throw new RuntimeException(
                    'The profile could not be shortlisted.'
                );
            }
        } catch (Throwable $exception) {
            /*
         * The PostgreSQL unique constraint remains the final
         * concurrency guard.
         */
            if (
                $this->shortlistModel
                ->hasShortlisted(
                    $userId,
                    $shortlistedUserId
                )
            ) {
                return true;
            }

            throw $exception;
        }

        return true;
    }

    /**
     * Determine whether the member has shortlisted the target.
     */
    public function hasShortlisted(
        int $userId,
        int $shortlistedUserId
    ): bool {
        return $this
            ->shortlistModel
            ->hasShortlisted(
                $userId,
                $shortlistedUserId
            );
    }

    /**
     * Return member IDs shortlisted by this member.
     *
     * @return list<int>
     */
    public function shortlistedMemberIds(
        int $userId
    ): array {
        return $this
            ->shortlistModel
            ->shortlistedMemberIds(
                $userId
            );
    }

    /**
     * Return IDs of members who shortlisted this member.
     *
     * @return list<int>
     */
    public function shortlistedByMemberIds(
        int $userId
    ): array {
        return $this
            ->shortlistModel
            ->shortlistedByMemberIds(
                $userId
            );
    }

    public function blockMember(
        int $blockerUserId,
        int $blockedUserId,
        string $comment
    ): void {
        $this->assertDistinctUsers(
            $blockerUserId,
            $blockedUserId
        );

        $this->assertActiveUser(
            $blockerUserId
        );

        $this->assertActiveUser(
            $blockedUserId
        );

        $normalizedComment =
            preg_replace(
                '/\s+/u',
                ' ',
                trim($comment)
            )
            ?? '';

        if (
            $normalizedComment === ''
            || mb_strlen(
                $normalizedComment
            ) > 250
        ) {
            throw new DomainException(
                'Please enter a comment of no more than 250 characters.'
            );
        }

        if (
            $this->blockModel
            ->blockerHasBlocked(
                $blockerUserId,
                $blockedUserId
            )
        ) {
            return;
        }

        $this->database->transBegin();

        try {
            /*
             * Serialize conflicting relationship changes for the member pair.
             */
            $this->database->query(
                'SELECT id FROM users '
                    . 'WHERE id IN (?, ?) '
                    . 'ORDER BY id '
                    . 'FOR UPDATE',
                [
                    $blockerUserId,
                    $blockedUserId,
                ]
            );

            if (
                !$this->blockModel
                    ->blockerHasBlocked(
                        $blockerUserId,
                        $blockedUserId
                    )
            ) {
                $insertId = $this
                    ->blockModel
                    ->insert(
                        [
                            'blocker_user_id' =>
                            $blockerUserId,

                            'blocked_user_id' =>
                            $blockedUserId,

                            'comment' =>
                            $normalizedComment,
                        ],
                        true
                    );

                if (!is_numeric($insertId)) {
                    throw new RuntimeException(
                        'The member could not be blocked.'
                    );
                }
            }

            /*
            * A blocked relationship must not remain actively shortlisted.
            *
            * Unlike Interest/View history, shortlist is active member state
            * rather than historical activity, so remove it in both directions.
            */
            $shortlistRemoved = $this
                ->shortlistModel
                ->removeBetween(
                    $blockerUserId,
                    $blockedUserId
                );

            if ($shortlistRemoved === false) {
                throw new RuntimeException(
                    'The shortlist relationship could not be cleared.'
                );
            }

            if (
                $this->database
                ->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The block transaction failed.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }

        /*
         * Existing interests and view history deliberately remain.
         *
         * They become invisible through the common block filter but are
         * retained for Admin history/statistics and future safety review.
         */
    }

    public function recordView(
        int $viewerUserId,
        int $viewedUserId
    ): void {
        $this->assertVisiblePair(
            $viewerUserId,
            $viewedUserId
        );

        $this->profileViewModel
            ->recordView(
                $viewerUserId,
                $viewedUserId
            );
    }

    public function isBlockedBetween(
        int $firstUserId,
        int $secondUserId
    ): bool {
        return $this
            ->blockModel
            ->existsBetween(
                $firstUserId,
                $secondUserId
            );
    }

    public function hasShownInterest(
        int $fromUserId,
        int $toUserId
    ): bool {
        return $this
            ->interestModel
            ->hasShown(
                $fromUserId,
                $toUserId
            );
    }

    public function hasInterestBetween(
        int $firstUserId,
        int $secondUserId
    ): bool {
        return $this
            ->interestModel
            ->existsBetween(
                $firstUserId,
                $secondUserId
            );
    }

    /**
     * Determine whether two members currently have
     * reciprocal Mutual Interest.
     */
    public function hasMutualInterestBetween(
        int $firstUserId,
        int $secondUserId
    ): bool {
        return $this
            ->interestModel
            ->isMutualBetween(
                $firstUserId,
                $secondUserId
            );
    }

    /**
     * @return list<int>
     */
    public function interestReceivedIds(
        int $userId
    ): array {
        return $this
            ->interestModel
            ->receivedMemberIds(
                $userId
            );
    }

    /**
     * @return list<int>
     */
    public function interestSentIds(
        int $userId
    ): array {
        return $this
            ->interestModel
            ->sentMemberIds(
                $userId
            );
    }

    /**
     * @return list<int>
     */
    public function profileVisitorIds(
        int $userId
    ): array {
        return $this
            ->profileViewModel
            ->viewerIdsFor(
                $userId
            );
    }

    /**
     * @return list<int>
     */
    public function profilesViewedIds(
        int $userId
    ): array {
        return $this
            ->profileViewModel
            ->viewedIdsFor(
                $userId
            );
    }

    /**
     * @return array{
     *     blocked:int,
     *     interestReceived:int,
     *     interestSent:int,
     *     uniqueProfileViewers:int,
     *     totalProfileViews:int
     * }
     */
    public function statsForMember(
        int $userId
    ): array {
        return [
            'blocked' =>
            $this
                ->blockModel
                ->countBlockedBy(
                    $userId
                ),

            'interestReceived' =>
            $this
                ->interestModel
                ->countReceived(
                    $userId
                ),

            'interestSent' =>
            $this
                ->interestModel
                ->countSent(
                    $userId
                ),

            'uniqueProfileViewers' =>
            $this
                ->profileViewModel
                ->uniqueViewerCount(
                    $userId
                ),

            'totalProfileViews' =>
            $this
                ->profileViewModel
                ->totalReceivedViews(
                    $userId
                ),
        ];
    }

    /**
     * Convert historical NULL/blank status values into the
     * effective state used by the Interest module.
     */
    private function normaliseInterestStatus(
        mixed $status
    ): string {
        $resolvedStatus =
            strtoupper(
                trim(
                    (string) $status
                )
            );

        if (
            $resolvedStatus === ''
        ) {
            return MemberInterestModel
            ::STATUS_PENDING;
        }

        return $resolvedStatus;
    }


    /**
     * Notify both members that reciprocal interest has been detected.
     *
     * Notifications remain in the same DB transaction as the
     * relationship state change.
     */
    private function createMutualInterestNotifications(
        int $firstUserId,
        int $secondUserId,
        int $interestId
    ): void {
        $firstUser =
            $this->userModel
            ->find(
                $firstUserId
            );

        $secondUser =
            $this->userModel
            ->find(
                $secondUserId
            );

        if (
            !is_array(
                $firstUser
            )
            || !is_array(
                $secondUser
            )
        ) {
            throw new RuntimeException(
                'The matched members could not be resolved.'
            );
        }

        $firstName =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) (
                        $firstUser['full_name']
                        ?? ''
                    )
                )
            )
            ?? '';

        $secondName =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) (
                        $secondUser['full_name']
                        ?? ''
                    )
                )
            )
            ?? '';

        if ($firstName === '') {
            $firstName =
                'A member';
        }

        if ($secondName === '') {
            $secondName =
                'A member';
        }

        $firstProfileReference =
            trim(
                (string) (
                    $firstUser['profile_ref_number']
                    ?? ''
                )
            );

        $secondProfileReference =
            trim(
                (string) (
                    $secondUser['profile_ref_number']
                    ?? ''
                )
            );

        if (
            $firstProfileReference
            === ''
            || $secondProfileReference
            === ''
        ) {
            throw new RuntimeException(
                'The matched member profiles could not be resolved.'
            );
        }

        /*
     * First member opens the second member.
     */
        $this->notificationService
            ->create(
                [
                    'recipientUserId' =>
                    $firstUserId,

                    'actorUserId' =>
                    $secondUserId,

                    'type' =>
                    \App\Models\MemberNotificationModel
                    ::TYPE_MUTUAL_INTEREST,

                    'title' =>
                    'It\'s a Match!',

                    'message' =>
                    $secondName
                        . ' also showed interest in you. '
                        . 'You now have a mutual interest.',

                    'entityType' =>
                    'MEMBER_INTEREST',

                    'entityId' =>
                    $interestId,

                    'targetUrl' =>
                    '/members/'
                        . rawurlencode(
                            $secondProfileReference
                        ),
                ]
            );

        /*
     * Second member opens the first member.
     */
        $this->notificationService
            ->create(
                [
                    'recipientUserId' =>
                    $secondUserId,

                    'actorUserId' =>
                    $firstUserId,

                    'type' =>
                    \App\Models\MemberNotificationModel
                    ::TYPE_MUTUAL_INTEREST,

                    'title' =>
                    'It\'s a Match!',

                    'message' =>
                    $firstName
                        . ' also showed interest in you. '
                        . 'You now have a mutual interest.',

                    'entityType' =>
                    'MEMBER_INTEREST',

                    'entityId' =>
                    $interestId,

                    'targetUrl' =>
                    '/members/'
                        . rawurlencode(
                            $firstProfileReference
                        ),
                ]
            );
    }

    /**
     * Create the notification generated by a newly-created interest.
     *
     * This method must be called only from the transaction that creates
     * the interest so the interaction and notification remain consistent.
     */
    private function createInterestReceivedNotification(
        int $fromUserId,
        int $toUserId,
        int $interestId
    ): void {
        $actor = $this
            ->userModel
            ->find(
                $fromUserId
            );

        if (!is_array($actor)) {
            throw new RuntimeException(
                'The interested member could not be resolved.'
            );
        }

        $actorName = preg_replace(
            '/\s+/u',
            ' ',
            trim(
                (string) (
                    $actor['full_name']
                    ?? ''
                )
            )
        ) ?? '';

        if ($actorName === '') {
            $actorName = 'A member';
        }

        $profileReference = trim(
            (string) (
                $actor['profile_ref_number']
                ?? ''
            )
        );

        if ($profileReference === '') {
            throw new RuntimeException(
                'The interested member profile could not be resolved.'
            );
        }

        /*
     * Keep notification targets as application-internal paths.
     *
     * MemberNotificationService performs an additional safety
     * validation before storing the target URL.
     */
        $targetUrl =
            '/members/'
            . rawurlencode(
                $profileReference
            );

        $this->notificationService
            ->create(
                [
                    /*
                 * The person RECEIVING the interest receives
                 * the notification.
                 */
                    'recipientUserId' =>
                    $toUserId,

                    /*
                 * The person who SHOWED interest is the actor.
                 */
                    'actorUserId' =>
                    $fromUserId,

                    'type' =>
                    \App\Models\MemberNotificationModel
                    ::TYPE_INTEREST_RECEIVED,

                    'title' =>
                    'New Interest',

                    'message' =>
                    $actorName
                        . ' has shown interest in your profile.',

                    /*
                 * Keep entity information available for future
                 * notification processing/auditing.
                 */
                    'entityType' =>
                    'MEMBER_INTEREST',

                    'entityId' =>
                    $interestId,

                    /*
                 * Opening the notification takes the recipient
                 * directly to the interested member's profile.
                 */
                    'targetUrl' =>
                    $targetUrl,
                ]
            );
    }

    private function assertVisiblePair(
        int $firstUserId,
        int $secondUserId
    ): void {
        $this->assertDistinctUsers(
            $firstUserId,
            $secondUserId
        );

        $this->assertActiveUser(
            $firstUserId
        );

        $this->assertActiveUser(
            $secondUserId
        );

        if (
            $this->isBlockedBetween(
                $firstUserId,
                $secondUserId
            )
        ) {
            /*
             * Intentionally generic so block state is not unnecessarily
             * disclosed.
             */
            throw new DomainException(
                'The member profile is unavailable.'
            );
        }
    }

    private function assertDistinctUsers(
        int $firstUserId,
        int $secondUserId
    ): void {
        if (
            $firstUserId <= 0
            || $secondUserId <= 0
            || $firstUserId === $secondUserId
        ) {
            throw new DomainException(
                'The selected member is invalid.'
            );
        }
    }

    private function assertActiveUser(
        int $userId
    ): void {
        $user = $this->userModel->find(
            $userId
        );

        if (
            !is_array($user)
            || strtoupper(
                trim(
                    (string) (
                        $user['account_status']
                        ?? ''
                    )
                )
            ) !== UserModel::STATUS_ACTIVE
        ) {
            throw new DomainException(
                'The member profile is unavailable.'
            );
        }
    }
}
