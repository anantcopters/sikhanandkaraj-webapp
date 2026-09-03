<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\MemberNotificationModel;
use App\Models\MemberVideoIntroductionModel;
use App\Models\MemberVideoModerationHistoryModel;
use App\Services\Aws\CloudFrontService;
use App\Services\Notification\MemberNotificationService;
use App\Services\Profile\MemberTrustVerificationService;
use App\Services\Profile\MemberPhotoUrlService;
use App\Services\Communication\CommunicationEventRegistry;
use App\Models\UserModel;
use App\Services\Email\MemberEmailService;
use CodeIgniter\Database\BaseConnection;
use Config\VideoIntroduction;
use DomainException;
use RuntimeException;
use Throwable;

final class MemberVideoModerationService
{
    public function __construct(
        private readonly MemberVideoIntroductionModel
        $videoModel,

        private readonly MemberVideoModerationHistoryModel
        $historyModel,

        private readonly UserModel
        $userModel,

        private readonly CloudFrontService
        $cloudFrontService,

        private readonly MemberNotificationService
        $notificationService,

        private readonly MemberEmailService
        $memberEmailService,

        private readonly MemberPhotoUrlService
        $photoUrlService,

        private readonly MemberTrustVerificationService
        $trustService,

        private readonly BaseConnection
        $database,

        private readonly VideoIntroduction
        $config
    ) {}

    /**
     * Return Video Introduction submissions for the Admin listing.
     *
     * @return list<array<string, mixed>>
     */
    public function listing(
        string $status = 'ALL'
    ): array {
        return $this->videoModel
            ->adminListing(
                $status
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function review(
        string $publicId
    ): array {
        $video = $this->videoModel
            ->findForAdminReview(
                trim($publicId)
            );

        if (! is_array($video)) {
            throw new DomainException(
                'The Video Introduction could not be found.'
            );
        }

        $playbackKey = trim(
            (string) (
                $video['playback_object_key']
                ?? ''
            )
        );

        $playbackUrl = '';

        if ($playbackKey !== '') {
            $playbackUrl =
                $this->cloudFrontService->signedUrl(
                    $playbackKey,
                    $this->config
                        ->playbackUrlTtlSeconds
                );
        }

        $videoHistory = $this->videoModel
            ->historyForMember(
                (int) $video['member_user_id']
            );

        if ($videoHistory !== []) {
            $videoIds = array_values(
                array_filter(
                    array_map(
                        static fn(array $item): int =>
                        (int) (
                            $item['id']
                            ?? 0
                        ),
                        $videoHistory
                    )
                )
            );

            $moderationRows = $videoIds !== []
                ? $this->historyModel
                ->whereIn(
                    'video_introduction_id',
                    $videoIds
                )
                ->orderBy(
                    'created_at',
                    'DESC'
                )
                ->findAll()
                : [];

            $historyByVideo = [];

            foreach ($moderationRows as $row) {
                $videoId = (int) (
                    $row['video_introduction_id']
                    ?? 0
                );

                $historyByVideo[$videoId][] =
                    $row;
            }

            foreach (
                $videoHistory as &$historyVideo
            ) {
                $historyVideoId = (int) (
                    $historyVideo['id']
                    ?? 0
                );

                $historyVideo['moderation_history'] =
                    $historyByVideo[$historyVideoId]
                    ?? [];
            }

            unset($historyVideo);
        }

        $memberUserId = (int) (
            $video['member_user_id']
            ?? 0
        );

        $status = mb_strtoupper(
            trim(
                (string) (
                    $video['moderation_status']
                    ?? ''
                )
            )
        );

        return [
            'video' =>
            $video,

            'playbackUrl' =>
            $playbackUrl,

            'canModerate' =>
            $status
                === MemberVideoIntroductionModel::STATUS_PENDING_REVIEW,

            'videoHistory' =>
            $videoHistory,

            'memberPhotos' =>
            $this->photoUrlService
                ->getAdminThumbnailPhotos(
                    $memberUserId
                ),

            'trustVerification' =>
            $this->trustService
                ->getForUser(
                    $memberUserId
                ),
        ];
    }

    public function moderate(
        string $publicId,
        int $adminUserId,
        string $decision,
        string $reason = ''
    ): void {
        $decision = mb_strtoupper(
            trim($decision)
        );

        $reason = mb_substr(
            preg_replace(
                '/\s+/u',
                ' ',
                trim($reason)
            ) ?? '',
            0,
            500
        );

        $targetStatus = match ($decision) {
            'APPROVE' =>
            MemberVideoIntroductionModel::STATUS_APPROVED,

            'REJECT' =>
            MemberVideoIntroductionModel::STATUS_REJECTED,

            'RESUBMIT' =>
            MemberVideoIntroductionModel::STATUS_RESUBMISSION_REQUESTED,

            default =>
            throw new DomainException(
                'Please select a valid moderation decision.'
            ),
        };

        if (
            $targetStatus
            !== MemberVideoIntroductionModel::STATUS_APPROVED
            && mb_strlen($reason) < 10
        ) {
            throw new DomainException(
                'Please provide a clear reason '
                    . 'of at least 10 characters.'
            );
        }

        $this->database->transBegin();

        try {
            $video = $this->database
                ->query(
                    'SELECT *
                     FROM member_video_introductions
                     WHERE public_id = ?
                     FOR UPDATE',
                    [
                        $publicId,
                    ]
                )
                ->getRowArray();

            if (
                ! is_array($video)
                || ($video['moderation_status'] ?? '')
                !== MemberVideoIntroductionModel::STATUS_PENDING_REVIEW
            ) {
                throw new DomainException(
                    'This Video Introduction is no longer '
                        . 'pending review.'
                );
            }

            $videoId = (int) $video['id'];

            $memberId = (int) $video['member_user_id'];

            $now = date('Y-m-d H:i:sP');

            if (
                $targetStatus
                === MemberVideoIntroductionModel::STATUS_APPROVED
            ) {
                $this->videoModel
                    ->where(
                        'member_user_id',
                        $memberId
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->where(
                        'id !=',
                        $videoId
                    )
                    ->set(
                        [
                            'is_active' => false,

                            'moderation_status' =>
                            MemberVideoIntroductionModel::STATUS_REPLACED,
                        ]
                    )
                    ->update();
            }

            $updated = $this->videoModel
                ->where(
                    'id',
                    $videoId
                )
                ->where(
                    'moderation_status',
                    MemberVideoIntroductionModel::STATUS_PENDING_REVIEW
                )
                ->set(
                    [
                        'moderation_status' =>
                        $targetStatus,

                        'approved_at' =>
                        $targetStatus
                            === MemberVideoIntroductionModel::STATUS_APPROVED
                            ? $now
                            : null,

                        'approved_by_admin_id' =>
                        $targetStatus
                            === MemberVideoIntroductionModel::STATUS_APPROVED
                            ? $adminUserId
                            : null,

                        'moderated_at' =>
                        $now,

                        'moderated_by_admin_id' =>
                        $adminUserId,

                        'rejection_reason' =>
                        $reason !== ''
                            ? $reason
                            : null,

                        'is_active' =>
                        $targetStatus
                            === MemberVideoIntroductionModel::STATUS_APPROVED,
                    ]
                )
                ->update();

            if (
                $updated !== true
                || $this->database->affectedRows() !== 1
            ) {
                throw new RuntimeException(
                    'The moderation decision could not be saved.'
                );
            }

            $this->historyModel->insert(
                [
                    'video_introduction_id' =>
                    $videoId,

                    'admin_user_id' =>
                    $adminUserId,

                    'from_status' =>
                    MemberVideoIntroductionModel::STATUS_PENDING_REVIEW,

                    'to_status' =>
                    $targetStatus,

                    'reason' =>
                    $reason !== ''
                        ? $reason
                        : null,

                    'created_at' =>
                    $now,
                ]
            );

            [
                $title,
                $message,
            ] = match ($targetStatus) {
                MemberVideoIntroductionModel::STATUS_APPROVED => [
                    'Video Introduction approved',

                    'Your Video Introduction has been '
                        . 'approved and follows your selected '
                        . 'privacy setting.',
                ],

                MemberVideoIntroductionModel::STATUS_REJECTED => [
                    'Video Introduction not approved',

                    'Your Video Introduction was not approved. '
                        . 'Reason: '
                        . $reason,
                ],

                default => [
                    'Video Introduction resubmission requested',

                    'Please record your Video Introduction again. '
                        . 'Reason: '
                        . $reason,
                ],
            };

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }

        $notificationType =
            match ($targetStatus) {
                MemberVideoIntroductionModel::STATUS_APPROVED =>
                CommunicationEventRegistry
                ::VIDEO_APPROVED,

                MemberVideoIntroductionModel::STATUS_REJECTED =>
                CommunicationEventRegistry
                ::VIDEO_REJECTED,

                default =>
                CommunicationEventRegistry
                ::VIDEO_RESUBMISSION_REQUESTED,
            };

        /*
        * Application notification is an independent
        * downstream communication channel.
        */
        try {
            $this->notificationService
                ->create([
                    'recipientUserId' =>
                    $memberId,

                    'type' =>
                    $notificationType,

                    'title' =>
                    $title,

                    'message' =>
                    $message,

                    'entityType' =>
                    'VIDEO_INTRODUCTION',

                    'entityId' =>
                    $videoId,

                    'targetUrl' =>
                    route_to(
                        'web.account.settings.section',
                        'video-introduction'
                    ),
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Video moderation notification failed for '
                    . 'member {memberId}, video {videoId}: {message}',
                [
                    'memberId' =>
                    $memberId,

                    'videoId' =>
                    $videoId,

                    'message' =>
                    $exception->getMessage(),
                ]
            );
        }

        /*
        * Email is independent from the application
        * notification.
        */
        try {
            $member =
                $this->userModel
                ->find(
                    $memberId
                );

            $this->memberEmailService
                ->queueVideoModeration(
                    recipientUserId: $memberId,

                    recipientName: is_array($member)
                        ? trim(
                            (string) (
                                $member['full_name']
                                ?? ''
                            )
                        )
                        : '',

                    videoId: $videoId,

                    status: $targetStatus,

                    reason: $reason
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Video moderation email queue failed for '
                    . 'member {memberId}, video {videoId}: {message}',
                [
                    'memberId' =>
                    $memberId,

                    'videoId' =>
                    $videoId,

                    'message' =>
                    $exception->getMessage(),
                ]
            );
        }
    }
}
