<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\MemberBlockModel;
use App\Models\MemberInterestModel;
use App\Models\MemberProfileViewModel;
use App\Models\UserModel;
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
        private readonly MemberProfileViewModel $profileViewModel,
        private readonly BaseConnection $database
    ) {}

    public function showInterest(
        int $fromUserId,
        int $toUserId
    ): bool {
        $this->assertVisiblePair(
            $fromUserId,
            $toUserId
        );

        /*
         * Repeated clicks are idempotent from the member's point of view.
         */
        if (
            $this->interestModel->hasShown(
                $fromUserId,
                $toUserId
            )
        ) {
            return false;
        }

        try {
            $insertId = $this
                ->interestModel
                ->insert(
                    [
                        'from_user_id' =>
                        $fromUserId,

                        'to_user_id' =>
                        $toUserId,
                    ],
                    true
                );

            if (!is_numeric($insertId)) {
                throw new RuntimeException(
                    'The interest could not be saved.'
                );
            }
        } catch (Throwable $exception) {
            /*
             * The DB unique constraint is the final concurrency guard.
             *
             * If another request inserted the same interest concurrently,
             * the required end-state has already been achieved.
             */
            if (
                $this->interestModel->hasShown(
                    $fromUserId,
                    $toUserId
                )
            ) {
                return false;
            }

            throw $exception;
        }

        return true;
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
