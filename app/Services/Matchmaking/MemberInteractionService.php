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
use App\Support\MemberNameVisibility;
use App\Services\Membership\MembershipEntitlementService;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Owns member-to-member interaction state changes.
 */
final class MemberInteractionService
{

    public const INTEREST_STATE_NONE =
    'NONE';

    public const INTEREST_STATE_PENDING_SENT =
    'PENDING_SENT';

    public const INTEREST_STATE_PENDING_RECEIVED =
    'PENDING_RECEIVED';

    public const INTEREST_STATE_ACCEPTED_SENT =
    'ACCEPTED_SENT';

    public const INTEREST_STATE_ACCEPTED_RECEIVED =
    'ACCEPTED_RECEIVED';

    public const INTEREST_STATE_DECLINED_SENT =
    'DECLINED_SENT';

    public const INTEREST_STATE_DECLINED_RECEIVED =
    'DECLINED_RECEIVED';

    public function __construct(
        private readonly UserModel
        $userModel,

        private readonly MemberBlockModel
        $blockModel,

        private readonly MemberInterestModel
        $interestModel,

        private readonly MemberShortlistModel
        $shortlistModel,

        private readonly MemberProfileViewModel
        $profileViewModel,

        private readonly MemberNotificationService
        $notificationService,

        private readonly BaseConnection
        $database,

        /*
        * MemberInteractionService owns the actual Shortlist state transition.
        *
        * Therefore membership authorization belongs here rather than only in
        * MemberProfileController.
        */
        private readonly MembershipEntitlementService
        $membershipEntitlementService
    ) {}

    /**
     * Show Interest in another active and visible member.
     *
     * Only one new Interest relationship is allowed between
     * a member pair.
     *
     * Therefore:
     *
     * A -> B already exists
     * OR
     * B -> A already exists
     *
     * means another Interest cannot be created.
     *
     * @return bool TRUE when a new Interest was created,
     *              FALSE when a relationship already exists.
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
     * Fast path.
     *
     * Check either direction, not only from -> to.
     */
        if (
            $this->interestModel
            ->existsBetween(
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
         * Serialize Interest creation for the member pair.
         *
         * Ordering IDs ensures A -> B and B -> A requests
         * acquire locks in the same order.
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
         * Recheck after locking.
         *
         * This prevents simultaneous reverse Interest
         * submissions from creating two rows.
         */
            if (
                $this->interestModel
                ->existsBetween(
                    $fromUserId,
                    $toUserId
                )
            ) {
                $this->database
                    ->transCommit();

                return false;
            }

            $insertId = $this
                ->interestModel
                ->insert(
                    [
                        'from_user_id' =>
                        $fromUserId,

                        'to_user_id' =>
                        $toUserId,

                        'status' =>
                        MemberInterestModel
                        ::STATUS_PENDING,

                        'responded_at' =>
                        null,
                    ],
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

            /*
         * Existing notification workflow remains intact.
         */
            $this->createInterestReceivedNotification(
                fromUserId: $fromUserId,

                toUserId: $toUserId,

                interestId: (int) $insertId
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
     * Add or remove another member from the authenticated member's shortlist.
     *
     * Shortlist is a paid membership capability.
     *
     * IMPORTANT:
     *
     * Authorization is performed here in the domain service rather than only
     * inside a controller. This protects every current and future caller,
     * including Profile View, Profile Card, Interest Card or an API endpoint.
     *
     * @return bool TRUE when shortlisted after the operation,
     *              FALSE when removed.
     */
    public function toggleShortlist(
        int $userId,
        int $shortlistedUserId
    ): bool {
        /*
        * Determine current state before applying the paid entitlement.
        *
        * PRODUCT RULE:
        *
        * A member who created a Shortlist while Paid may later become Free.
        *
        * We retain that historical Shortlist, but a Free member must still be able
        * to REMOVE it. Otherwise membership expiry would trap stale user data.
        *
        * Therefore:
        *
        * - adding a NEW Shortlist requires paid entitlement;
        * - removing an EXISTING Shortlist remains allowed.
        */
        $isAlreadyShortlisted =
            $this->shortlistModel
            ->hasShortlisted(
                $userId,
                $shortlistedUserId
            );

        if (
            !$isAlreadyShortlisted
            && !$this->membershipEntitlementService
                ->canShortlist(
                    $userId
                )
        ) {
            throw new DomainException(
                'Shortlisting profiles is available with a paid membership. '
                    . 'Please upgrade your plan to use Shortlist.'
            );
        }

        /*
     * Reuse exactly the same member-pair authorization used by Interest and
     * profile interactions.
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

        if ($isAlreadyShortlisted) {
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
         * PostgreSQL uniqueness remains the final concurrency guard.
         *
         * If another request inserted the same shortlist concurrently,
         * returning TRUE accurately describes the resulting domain state.
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
     * Return whether the viewer currently has the target profile shortlisted.
     *
     * This is read-only presentation state.
     *
     * It deliberately does not apply the current membership entitlement because
     * historical Shortlists survive membership expiry.
     *
     * Membership entitlement is required only when creating a NEW shortlist.
     */
    public function isShortlisted(
        int $viewerUserId,
        int $targetUserId
    ): bool {
        if (
            $viewerUserId <= 0
            || $targetUserId <= 0
            || $viewerUserId === $targetUserId
        ) {
            return false;
        }

        return $this->shortlistModel
            ->hasShortlisted(
                $viewerUserId,
                $targetUserId
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
     * Resolve the complete member-facing Interest relationship
     * between the viewer and another member.
     *
     * Direction is always relative to the viewer.
     *
     * @return array{
     *     state:string,
     *     hasRelationship:bool,
     *     hasOutgoing:bool,
     *     hasIncoming:bool,
     *     canShowInterest:bool,
     *     canRespond:bool,
     *     outgoingStatus:?string,
     *     incomingStatus:?string
     * }
     */
    public function interestRelationshipFor(
        int $viewerUserId,
        int $targetUserId
    ): array {
        $this->assertDistinctUsers(
            $viewerUserId,
            $targetUserId
        );

        $outgoingInterest =
            $this->interestModel
            ->findBetween(
                $viewerUserId,
                $targetUserId
            );

        $incomingInterest =
            $this->interestModel
            ->findBetween(
                $targetUserId,
                $viewerUserId
            );

        $outgoingStatus =
            is_array(
                $outgoingInterest
            )
            ? $this->normaliseInterestStatus(
                $outgoingInterest['status']
                    ?? null
            )
            : null;

        $incomingStatus =
            is_array(
                $incomingInterest
            )
            ? $this->normaliseInterestStatus(
                $incomingInterest['status']
                    ?? null
            )
            : null;

        /*
     * ACCEPTED is the strongest final positive state.
     *
     * This also safely handles old reciprocal rows that
     * were migrated from MUTUAL to ACCEPTED.
     */
        if (
            $outgoingStatus
            === MemberInterestModel
            ::STATUS_ACCEPTED
        ) {
            return $this
                ->interestRelationshipResult(
                    state: self::INTEREST_STATE_ACCEPTED_SENT,

                    outgoingStatus: $outgoingStatus,

                    incomingStatus: $incomingStatus
                );
        }

        if (
            $incomingStatus
            === MemberInterestModel
            ::STATUS_ACCEPTED
        ) {
            return $this
                ->interestRelationshipResult(
                    state: self::INTEREST_STATE_ACCEPTED_RECEIVED,

                    outgoingStatus: $outgoingStatus,

                    incomingStatus: $incomingStatus
                );
        }

        /*
     * Incoming Pending takes precedence over historical
     * Declined state because it represents a current
     * actionable request.
     */
        if (
            $incomingStatus
            === MemberInterestModel
            ::STATUS_PENDING
        ) {
            return $this
                ->interestRelationshipResult(
                    state: self::INTEREST_STATE_PENDING_RECEIVED,

                    outgoingStatus: $outgoingStatus,

                    incomingStatus: $incomingStatus
                );
        }

        if (
            $outgoingStatus
            === MemberInterestModel
            ::STATUS_PENDING
        ) {
            return $this
                ->interestRelationshipResult(
                    state: self::INTEREST_STATE_PENDING_SENT,

                    outgoingStatus: $outgoingStatus,

                    incomingStatus: $incomingStatus
                );
        }

        if (
            $outgoingStatus
            === MemberInterestModel
            ::STATUS_DECLINED
        ) {
            return $this
                ->interestRelationshipResult(
                    state: self::INTEREST_STATE_DECLINED_SENT,

                    outgoingStatus: $outgoingStatus,

                    incomingStatus: $incomingStatus
                );
        }

        if (
            $incomingStatus
            === MemberInterestModel
            ::STATUS_DECLINED
        ) {
            return $this
                ->interestRelationshipResult(
                    state: self::INTEREST_STATE_DECLINED_RECEIVED,

                    outgoingStatus: $outgoingStatus,

                    incomingStatus: $incomingStatus
                );
        }

        return $this
            ->interestRelationshipResult(
                state: self::INTEREST_STATE_NONE,

                outgoingStatus: null,

                incomingStatus: null
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
     * Build the normalized profile presentation state.
     *
     * @return array{
     *     state:string,
     *     hasRelationship:bool,
     *     hasOutgoing:bool,
     *     hasIncoming:bool,
     *     canShowInterest:bool,
     *     canRespond:bool,
     *     outgoingStatus:?string,
     *     incomingStatus:?string
     * }
     */
    private function interestRelationshipResult(
        string $state,
        ?string $outgoingStatus,
        ?string $incomingStatus
    ): array {
        return [
            'state' =>
            $state,

            'hasRelationship' =>
            $outgoingStatus !== null
                || $incomingStatus !== null,

            'hasOutgoing' =>
            $outgoingStatus !== null,

            'hasIncoming' =>
            $incomingStatus !== null,

            'canShowInterest' =>
            $state
                === self::INTEREST_STATE_NONE,

            'canRespond' =>
            $state
                === self::INTEREST_STATE_PENDING_RECEIVED,

            'outgoingStatus' =>
            $outgoingStatus,

            'incomingStatus' =>
            $incomingStatus,
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

        $storedActorName =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) (
                        $actor['full_name']
                        ?? ''
                    )
                )
            )
            ?? '';

        /*
        * The notification recipient is another member.
        *
        * Apply the same centralized female-name privacy rule used
        * by member cards and the other-member Full Profile view.
        *
        * A future paid-member entitlement may replace false with
        * the recipient's resolved full-name access permission.
        */
        $actorName =
            $storedActorName !== ''
            ? MemberNameVisibility::forDisplay(
                fullName: $storedActorName,

                gender: $actor['gender']
                    ?? '',

                canViewFullName: false
            )
            : 'A member';

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
                 * The person receiving the Interest receives
                 * the notification.
                 */
                    'recipientUserId' =>
                    $toUserId,

                    /*
                 * The person who showed Interest is the actor.
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
                 * notification processing and auditing.
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
