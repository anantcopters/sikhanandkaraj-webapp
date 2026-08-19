<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Models\MemberBlockModel;
use App\Models\MemberInterestModel;
use App\Models\MemberNotificationModel;
use App\Models\MemberProfileReportModel;
use App\Models\MemberVideoIntroductionModel;
use App\Models\MemberVideoProcessingJobModel;
use App\Models\UserModel;
use App\Services\Aws\CloudFrontService;
use App\Services\Aws\S3Service;
use App\Services\Notification\MemberNotificationService;
use App\Support\BooleanValue;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\VideoIntroduction;
use DomainException;
use RuntimeException;
use Throwable;

final class MemberVideoIntroductionService
{
    public function __construct(
        private readonly MemberVideoIntroductionModel $videoModel,
        private readonly MemberVideoProcessingJobModel $jobModel,
        private readonly UserModel $userModel,
        private readonly MemberInterestModel $interestModel,
        private readonly MemberBlockModel $blockModel,
        private readonly MemberProfileReportModel $profileReportModel,
        private readonly S3Service $s3Service,
        private readonly CloudFrontService $cloudFrontService,
        private readonly MemberNotificationService $notificationService,
        private readonly BaseConnection $database,
        private readonly VideoIntroduction $config
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function settingsForMember(
        int $memberUserId
    ): array {
        $member = $this->requireActiveMember(
            $memberUserId
        );

        $current = $this->videoModel->currentForMember(
            $memberUserId
        );

        $active = $this->videoModel->activeForMember(
            $memberUserId
        );

        $gender = mb_strtoupper(
            trim(
                (string) ($member['gender'] ?? '')
            )
        );

        $isFemale = in_array(
            $gender,
            ['F', 'FEMALE'],
            true
        );

        $status = is_array($current)
            ? mb_strtoupper(
                (string) ($current['moderation_status'] ?? '')
            )
            : 'NOT_SUBMITTED';

        $lockedUntil = is_array($current)
            ? strtotime(
                (string) ($current['locked_until'] ?? '')
            )
            : false;

        $isLocked = $lockedUntil !== false
            && $lockedUntil > time();

        return [
            'videoIntroduction' => $current,
            'activeVideoIntroduction' => $active,
            'videoStatus' => $status,
            'videoStatusLabel' => $this->statusLabel($status),
            'isFemaleMember' => $isFemale,

            /*
             * Until individual plan entitlements are introduced,
             * users.is_paid = TRUE is considered a Pro member.
             */
            'isProMember' => BooleanValue::fromDatabase(
                $member['is_paid'] ?? false
            ),

            'canRecord' => ! is_array($current)
                || in_array(
                    $status,
                    [
                        MemberVideoIntroductionModel::STATUS_PROCESSING_FAILED,
                        MemberVideoIntroductionModel::STATUS_REJECTED,
                        MemberVideoIntroductionModel::STATUS_RESUBMISSION_REQUESTED,
                        MemberVideoIntroductionModel::STATUS_DELETED,
                    ],
                    true
                )
                || ! $isLocked,

            'canDelete' => is_array($current)
                && ! $isLocked
                && ! in_array(
                    $status,
                    [
                        MemberVideoIntroductionModel::STATUS_PROCESSING,
                        MemberVideoIntroductionModel::STATUS_PENDING_REVIEW,
                        MemberVideoIntroductionModel::STATUS_DELETED,
                    ],
                    true
                ),

            'canHide' => is_array($active),

            'isHidden' => is_array($active)
                && ($active['visibility'] ?? '')
                === MemberVideoIntroductionModel::VISIBILITY_HIDDEN,

            'lockRemainingSeconds' => $isLocked
                ? $lockedUntil - time()
                : 0,

            'allowedVisibilities' => $isFemale
                ? [
                    MemberVideoIntroductionModel::VISIBILITY_ACCEPTED_INTEREST,
                    MemberVideoIntroductionModel::VISIBILITY_HIDDEN,
                ]
                : [
                    MemberVideoIntroductionModel::VISIBILITY_PRO,
                    MemberVideoIntroductionModel::VISIBILITY_ACCEPTED_INTEREST,
                    MemberVideoIntroductionModel::VISIBILITY_HIDDEN,
                ],

            'consentVersion' => $this->config->consentVersion,
            'minimumDurationSeconds' =>
            $this->config->minimumDurationSeconds,
            'maximumDurationSeconds' =>
            $this->config->maximumDurationSeconds,
            'maximumUploadSizeKb' =>
            $this->config->maximumUploadSizeKb,
        ];
    }

    /**
     * @return array{publicId:string}
     */
    public function submit(
        int $memberUserId,
        UploadedFile $upload,
        bool $consentAccepted
    ): array {
        $member = $this->requireActiveMember(
            $memberUserId
        );

        if (! $consentAccepted) {
            throw new DomainException(
                'You must accept the Video Introduction guidelines.'
            );
        }

        $current = $this->videoModel->currentForMember(
            $memberUserId
        );

        if (is_array($current)) {
            $status = mb_strtoupper(
                (string) ($current['moderation_status'] ?? '')
            );

            $lockedUntil = strtotime(
                (string) ($current['locked_until'] ?? '')
            );

            $corrective = in_array(
                $status,
                [
                    MemberVideoIntroductionModel::STATUS_PROCESSING_FAILED,
                    MemberVideoIntroductionModel::STATUS_REJECTED,
                    MemberVideoIntroductionModel::STATUS_RESUBMISSION_REQUESTED,
                    MemberVideoIntroductionModel::STATUS_DELETED,
                ],
                true
            );

            if (
                ! $corrective
                && $lockedUntil !== false
                && $lockedUntil > time()
            ) {
                throw new DomainException(
                    'This Video Introduction cannot be replaced '
                        . 'until the seven-day lock expires.'
                );
            }

            if (
                in_array(
                    $status,
                    [
                        MemberVideoIntroductionModel::STATUS_PROCESSING,
                        MemberVideoIntroductionModel::STATUS_PENDING_REVIEW,
                    ],
                    true
                )
            ) {
                throw new DomainException(
                    'Your current Video Introduction is still '
                        . 'being processed or reviewed.'
                );
            }
        }

        if (
            ! $upload->isValid()
            || $upload->hasMoved()
        ) {
            throw new DomainException(
                'Please record the Video Introduction again.'
            );
        }

        $size = $upload->getSize();

        if (
            $size <= 0
            || $size > ($this->config->maximumUploadSizeKb * 1024)
        ) {
            throw new DomainException(
                'The recorded video exceeds the allowed file size.'
            );
        }

        $mime = mb_strtolower(
            trim(
                $upload->getMimeType()
            )
        );

        if (
            ! isset(
                $this->config->allowedMimeTypes[$mime]
            )
        ) {
            throw new DomainException(
                'This browser video format is not supported.'
            );
        }

        $publicId = $this->uuidV4();

        $extension =
            $this->config->allowedMimeTypes[$mime];

        $objectKey =
            'members/video-introduction/original/'
            . $publicId
            . '.'
            . $extension;

        $localPath = $upload->getTempName();

        $this->s3Service->upload(
            $localPath,
            $objectKey,
            $mime,
            [
                'media-type' =>
                'member-video-introduction',
                'public-id' => $publicId,
            ],
            'inline; filename="video-introduction.'
                . $extension
                . '"'
        );

        $videoId = 0;

        $this->database->transBegin();

        try {
            $defaultVisibility = in_array(
                mb_strtoupper(
                    (string) ($member['gender'] ?? '')
                ),
                ['F', 'FEMALE'],
                true
            )
                ? MemberVideoIntroductionModel::VISIBILITY_ACCEPTED_INTEREST
                : MemberVideoIntroductionModel::VISIBILITY_PRO;

            $now = date('Y-m-d H:i:sP');

            $lockedUntil = date(
                'Y-m-d H:i:sP',
                strtotime(
                    '+'
                        . $this->config->lockDays
                        . ' days'
                )
            );

            $inserted = $this->videoModel->insert(
                [
                    'public_id' => $publicId,
                    'member_user_id' => $memberUserId,

                    'version_number' =>
                    $this->videoModel
                        ->nextVersionNumber(
                            $memberUserId
                        ),

                    'moderation_status' =>
                    MemberVideoIntroductionModel::STATUS_PROCESSING,

                    'visibility' => $defaultVisibility,

                    'consent_version' =>
                    $this->config->consentVersion,

                    'consented_at' => $now,
                    'original_object_key' => $objectKey,
                    'source_mime_type' => $mime,
                    'source_size_bytes' => $size,
                    'submitted_at' => $now,
                    'locked_until' => $lockedUntil,
                    'is_active' => false,
                ],
                true
            );

            $videoId = is_numeric($inserted)
                ? (int) $inserted
                : 0;

            if ($videoId <= 0) {
                throw new RuntimeException(
                    'The Video Introduction record '
                        . 'could not be created.'
                );
            }

            $jobCreated = $this->jobModel->insert(
                [
                    'video_introduction_id' => $videoId,
                    'status' => 'PENDING',
                    'attempt_count' => 0,
                    'available_at' => $now,
                ]
            );

            if ($jobCreated === false) {
                throw new RuntimeException(
                    'The Video Introduction processing '
                        . 'job could not be queued.'
                );
            }

            $this->notificationService->create(
                [
                    'recipientUserId' => $memberUserId,
                    'type' =>
                    MemberNotificationModel::TYPE_SYSTEM,
                    'title' =>
                    'Video Introduction saved',
                    'message' =>
                    'Your Video Introduction was saved '
                        . 'and will continue processing '
                        . 'in the background.',
                    'entityType' =>
                    'VIDEO_INTRODUCTION',
                    'entityId' => $videoId,
                    'targetUrl' =>
                    '/account-settings/video-introduction',
                ]
            );

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            $this->s3Service->delete(
                $objectKey
            );

            throw $exception;
        }

        return [
            'publicId' => $publicId,
        ];
    }

    public function updateVisibility(
        int $memberUserId,
        string $visibility
    ): void {
        $member = $this->requireActiveMember(
            $memberUserId
        );

        $active = $this->videoModel->activeForMember(
            $memberUserId
        );

        if (! is_array($active)) {
            throw new DomainException(
                'No approved Video Introduction is available.'
            );
        }

        $visibility = mb_strtoupper(
            trim($visibility)
        );

        $allowed = [
            MemberVideoIntroductionModel::VISIBILITY_PRO,
            MemberVideoIntroductionModel::VISIBILITY_ACCEPTED_INTEREST,
            MemberVideoIntroductionModel::VISIBILITY_HIDDEN,
        ];

        if (
            ! in_array(
                $visibility,
                $allowed,
                true
            )
        ) {
            throw new DomainException(
                'Please select a valid video visibility.'
            );
        }

        $isFemale = in_array(
            mb_strtoupper(
                (string) ($member['gender'] ?? '')
            ),
            ['F', 'FEMALE'],
            true
        );

        if (
            $isFemale
            && $visibility
            === MemberVideoIntroductionModel::VISIBILITY_PRO
        ) {
            throw new DomainException(
                'Female profiles can show the video only '
                    . 'after an Interest is accepted.'
            );
        }

        $updated = $this->videoModel->update(
            (int) $active['id'],
            [
                'visibility' => $visibility,

                'hidden_at' =>
                $visibility
                    === MemberVideoIntroductionModel::VISIBILITY_HIDDEN
                    ? date('Y-m-d H:i:sP')
                    : null,
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                'Video visibility could not be updated.'
            );
        }
    }

    public function delete(
        int $memberUserId
    ): void {
        $current = $this->videoModel->currentForMember(
            $memberUserId
        );

        if (! is_array($current)) {
            throw new DomainException(
                'No Video Introduction is available.'
            );
        }

        $active = $this->videoModel->activeForMember(
            $memberUserId
        );

        $target = is_array($active)
            ? $active
            : $current;

        $lockedUntil = strtotime(
            (string) ($target['locked_until'] ?? '')
        );

        if (
            $lockedUntil !== false
            && $lockedUntil > time()
        ) {
            throw new DomainException(
                'The Video Introduction cannot be deleted '
                    . 'until the seven-day lock expires. '
                    . 'You may hide it now.'
            );
        }

        $updated = $this->videoModel->update(
            (int) $target['id'],
            [
                'moderation_status' =>
                MemberVideoIntroductionModel::STATUS_DELETED,

                'visibility' =>
                MemberVideoIntroductionModel::VISIBILITY_HIDDEN,

                'is_active' => false,
                'hidden_at' => date('Y-m-d H:i:sP'),
                'deleted_at' => date('Y-m-d H:i:sP'),
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                'The Video Introduction could not be deleted.'
            );
        }
    }

    public function purgeExpiredAssets(
        int $limit = 50
    ): int {
        $rows = $this->videoModel
            ->groupStart()
            ->groupStart()
            ->where(
                'moderation_status',
                MemberVideoIntroductionModel::STATUS_DELETED
            )
            ->where(
                'deleted_at <=',
                date(
                    'Y-m-d H:i:sP',
                    strtotime('-24 hours')
                )
            )
            ->groupEnd()
            ->orGroupStart()
            ->whereIn(
                'moderation_status',
                [
                    MemberVideoIntroductionModel::STATUS_REJECTED,
                    MemberVideoIntroductionModel::STATUS_RESUBMISSION_REQUESTED,
                ]
            )
            ->where(
                'moderated_at <=',
                date(
                    'Y-m-d H:i:sP',
                    strtotime('-14 days')
                )
            )
            ->groupEnd()
            ->orGroupStart()
            ->where(
                'moderation_status',
                MemberVideoIntroductionModel::STATUS_REPLACED
            )
            ->where(
                'updated_at <=',
                date(
                    'Y-m-d H:i:sP',
                    strtotime('-7 days')
                )
            )
            ->groupEnd()
            ->groupEnd()
            ->where(
                'assets_purged_at',
                null
            )
            ->where(
                'is_active',
                false
            )
            ->orderBy(
                'id',
                'ASC'
            )
            ->findAll(
                max(
                    1,
                    min($limit, 200)
                )
            );

        $purged = 0;

        foreach ($rows as $row) {
            $keys = array_values(
                array_filter(
                    [
                        trim(
                            (string) (
                                $row['original_object_key']
                                ?? ''
                            )
                        ),
                        trim(
                            (string) (
                                $row['playback_object_key']
                                ?? ''
                            )
                        ),
                        trim(
                            (string) (
                                $row['poster_object_key']
                                ?? ''
                            )
                        ),
                    ]
                )
            );

            if (
                $this->s3Service->deleteMany(
                    $keys
                )
            ) {
                $this->videoModel->update(
                    (int) $row['id'],
                    [
                        'assets_purged_at' =>
                        date('Y-m-d H:i:sP'),
                    ]
                );

                $purged++;
            }
        }

        return $purged;
    }

    public function ownerPlaybackUrl(
        int $memberUserId
    ): string {
        $current = $this->videoModel->currentForMember(
            $memberUserId
        );

        $active = $this->videoModel->activeForMember(
            $memberUserId
        );

        $playable = is_array($active)
            ? $active
            : $current;

        if (
            ! is_array($playable)
            || ($playable['moderation_status'] ?? '')
            === MemberVideoIntroductionModel::STATUS_DELETED
            || trim(
                (string) (
                    $playable['playback_object_key']
                    ?? ''
                )
            ) === ''
        ) {
            throw new DomainException(
                'The Video Introduction is not ready '
                    . 'for playback.'
            );
        }

        return $this->cloudFrontService->signedUrl(
            (string) $playable['playback_object_key'],
            $this->config->playbackUrlTtlSeconds
        );
    }

    public function viewerPlaybackUrl(
        int $viewerUserId,
        int $ownerUserId
    ): string {
        if (
            $viewerUserId <= 0
            || $ownerUserId <= 0
            || $viewerUserId === $ownerUserId
        ) {
            throw new DomainException(
                'Video playback is not available.'
            );
        }

        $viewer = $this->requireActiveMember(
            $viewerUserId
        );

        $owner = $this->requireActiveMember(
            $ownerUserId
        );

        $video = $this->videoModel->activeForMember(
            $ownerUserId
        );

        if (
            ! is_array($video)
            || ($video['visibility'] ?? '')
            === MemberVideoIntroductionModel::VISIBILITY_HIDDEN
        ) {
            throw new DomainException(
                'This member has hidden their Video Introduction.'
            );
        }

        if (
            $this->blockModel->existsBetween(
                $viewerUserId,
                $ownerUserId
            )
        ) {
            throw new DomainException(
                'Video playback is not available.'
            );
        }

        if (
            $this->profileReportModel->isGloballyHidden(
                $ownerUserId
            )
        ) {
            throw new DomainException(
                'Video playback is not available.'
            );
        }

        if (
            mb_strtoupper(
                (string) ($viewer['gender'] ?? '')
            )
            === mb_strtoupper(
                (string) ($owner['gender'] ?? '')
            )
        ) {
            throw new DomainException(
                'Video playback is not available.'
            );
        }

        if (
            ($owner['profile_visibility'] ?? 'ALL_MEMBERS')
            === 'PAID_MEMBERS_ONLY'
            && ! BooleanValue::fromDatabase(
                $viewer['is_paid'] ?? false
            )
        ) {
            throw new DomainException(
                'A Pro membership is required '
                    . 'to view this profile.'
            );
        }

        $visibility = (string) (
            $video['visibility']
            ?? ''
        );

        if (
            $visibility
            === MemberVideoIntroductionModel::VISIBILITY_PRO
            && ! BooleanValue::fromDatabase(
                $viewer['is_paid'] ?? false
            )
        ) {
            throw new DomainException(
                'A Pro membership is required to view '
                    . 'this Video Introduction.'
            );
        }

        if (
            $visibility
            === MemberVideoIntroductionModel::VISIBILITY_ACCEPTED_INTEREST
            && ! $this->interestModel->acceptedBetween(
                $viewerUserId,
                $ownerUserId
            )
        ) {
            throw new DomainException(
                'This Video Introduction is available '
                    . 'after an Interest is accepted.'
            );
        }

        return $this->cloudFrontService->signedUrl(
            (string) $video['playback_object_key'],
            $this->config->playbackUrlTtlSeconds
        );
    }

    /**
     * @return array{hasBadge:bool,isHidden:bool}
     */
    public function profileState(
        int $ownerUserId
    ): array {
        $video = $this->videoModel->activeForMember(
            $ownerUserId
        );

        return [
            'hasBadge' => is_array($video),

            'isHidden' => is_array($video)
                && ($video['visibility'] ?? '')
                === MemberVideoIntroductionModel::VISIBILITY_HIDDEN,
        ];
    }

    public function viewerPlaybackUrlByProfileReference(
        int $viewerUserId,
        string $profileReference
    ): string {
        $owner = $this->userModel
            ->findActiveByProfileReference(
                mb_strtoupper(
                    trim($profileReference)
                )
            );

        if (! is_array($owner)) {
            throw new DomainException(
                'Video playback is not available.'
            );
        }

        return $this->viewerPlaybackUrl(
            $viewerUserId,
            (int) $owner['id']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function requireActiveMember(
        int $memberUserId
    ): array {
        $member = $this->userModel->find(
            $memberUserId
        );

        if (
            ! is_array($member)
            || ($member['account_status'] ?? '')
            !== UserModel::STATUS_ACTIVE
        ) {
            throw new DomainException(
                'The active member account could not be found.'
            );
        }

        return $member;
    }

    private function statusLabel(
        string $status
    ): string {
        return match ($status) {
            MemberVideoIntroductionModel::STATUS_PROCESSING =>
            'Processing',

            MemberVideoIntroductionModel::STATUS_PROCESSING_FAILED =>
            'Processing failed',

            MemberVideoIntroductionModel::STATUS_PENDING_REVIEW =>
            'Under review',

            MemberVideoIntroductionModel::STATUS_APPROVED =>
            'Approved',

            MemberVideoIntroductionModel::STATUS_REJECTED =>
            'Rejected',

            MemberVideoIntroductionModel::STATUS_RESUBMISSION_REQUESTED =>
            'Resubmission requested',

            MemberVideoIntroductionModel::STATUS_DELETED =>
            'Deleted',

            default =>
            'Not submitted',
        };
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);

        $bytes[6] = chr(
            (ord($bytes[6]) & 0x0f)
                | 0x40
        );

        $bytes[8] = chr(
            (ord($bytes[8]) & 0x3f)
                | 0x80
        );

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(
                bin2hex($bytes),
                4
            )
        );
    }
}
