<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Models\MemberBlockModel;
use App\Models\MemberInterestModel;
use App\Models\MemberProfileReportModel;
use App\Models\MemberVideoIntroductionModel;
use App\Models\MemberVideoProcessingJobModel;
use App\Models\UserModel;
use App\Services\Aws\CloudFrontService;
use App\Services\Aws\S3Service;
use App\Models\MemberPhotoModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use App\Services\Membership\LiveIntroductionAccessPolicy;
use App\Services\Membership\MembershipEntitlementService;
use Config\VideoIntroduction;
use DomainException;
use RuntimeException;
use Throwable;

final class MemberVideoIntroductionService
{
    public function __construct(
        private readonly MemberVideoIntroductionModel
        $videoModel,

        private readonly MemberVideoProcessingJobModel
        $jobModel,

        private readonly MemberPhotoModel
        $photoModel,

        private readonly UserModel
        $userModel,

        private readonly MemberInterestModel
        $interestModel,

        private readonly MemberBlockModel
        $blockModel,

        private readonly MemberProfileReportModel
        $profileReportModel,

        private readonly S3Service
        $s3Service,

        private readonly CloudFrontService
        $cloudFrontService,

        private readonly BaseConnection
        $database,

        private readonly VideoIntroduction
        $config,

        private readonly MembershipEntitlementService
        $membershipEntitlementService,

        private readonly LiveIntroductionAccessPolicy
        $liveIntroductionAccessPolicy
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

        /*
        * Recording a Live Introduction is a paid membership capability.
        *
        * Account Settings may still display the feature to a Free member so the
        * product can explain what is available, but recording/submission itself
        * must be enforced server-side.
        */
        $canCreateLiveIntroduction =
            $this->membershipEntitlementService
            ->canCreateLiveIntroduction(
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

        $hasApprovedProfilePhoto =
            $this->photoModel->countApprovedForMember(
                $memberUserId
            ) > 0;

        $canRecordForStatus =
            !is_array($current)
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
            || (
                !$isLocked
                && !in_array(
                    $status,
                    [
                        MemberVideoIntroductionModel::STATUS_PROCESSING,
                        MemberVideoIntroductionModel::STATUS_PENDING_REVIEW,
                    ],
                    true
                )
            );

        return [
            'videoIntroduction' => $current,
            'activeVideoIntroduction' => $active,
            'videoStatus' => $status,
            'videoStatusLabel' => $this->statusLabel($status),
            'isFemaleMember' => $isFemale,

            /*
            * Historical View code expects this presentation key.
            *
            * It now means "member currently owns the Live Introduction creation
            * capability", not "users.is_paid" and not specifically the PRO plan.
            *
            * This key can be renamed in a later pure presentation cleanup without
            * changing authorization behavior.
            */
            'isProMember' =>
            $canCreateLiveIntroduction,

            'videoMemberName' =>
            trim(
                (string) (
                    $member['full_name']
                    ?? 'Member'
                )
            ),

            'videoProfileReference' =>
            trim(
                (string) (
                    $member['profile_ref_number']
                    ?? ''
                )
            ),

            'hasApprovedProfilePhoto' =>
            $hasApprovedProfilePhoto,

            /*
            * Client/UI state mirrors the server entitlement but is never the security
            * boundary. submit() repeats the entitlement check.
            */
            'canRecord' =>
            $canCreateLiveIntroduction
                && $hasApprovedProfilePhoto
                && $canRecordForStatus,

            'canDelete' =>
            is_array($current)
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
            'videoIntroductionHistory' =>
            $this->videoHistoryForMember(
                $memberUserId
            ),
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

        /*
        * Server-side membership enforcement is mandatory.
        *
        * Hiding/disabling the recorder in the browser is not sufficient because a
        * crafted multipart request could otherwise bypass the UI.
        */
        if (
            !$this->membershipEntitlementService
                ->canCreateLiveIntroduction(
                    $memberUserId
                )
        ) {
            throw new DomainException(
                'A paid membership is required to create '
                    . 'a Live Introduction.'
            );
        }

        if (
            $this->photoModel->countApprovedForMember(
                $memberUserId
            ) < 1
        ) {
            throw new DomainException(
                'At least one approved profile photo '
                    . 'is required before recording a '
                    . 'Video Introduction.'
            );
        }

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
        /*
        * LiveIntroductionAccessPolicy is the single member-facing playback
        * authorization boundary.
        *
        * IMPORTANT:
        *
        * No CloudFront signed URL may be created before this policy succeeds.
        */
        $access = $this
            ->liveIntroductionAccessPolicy
            ->authorizePlayback(
                $viewerUserId,
                $ownerUserId
            );

        $video = $access['video'] ?? null;

        if (!is_array($video)) {
            throw new DomainException(
                'Video playback is not available.'
            );
        }

        $objectKey = trim(
            (string) (
                $video['playback_object_key']
                ?? ''
            )
        );

        if ($objectKey === '') {
            throw new DomainException(
                'Video playback is not available.'
            );
        }

        /*
        * Signed URL generation occurs only AFTER:
        *
        * - paid entitlement;
        * - protected profile relationship;
        * - Verified Profile;
        * - block/report protection;
        * - gender/Interest privacy;
        * - video moderation;
        * - video visibility;
        * - membership Live Introduction quota
        *
        * have all succeeded.
        */
        return $this
            ->cloudFrontService
            ->signedUrl(
                $objectKey,
                $this->config
                    ->playbackUrlTtlSeconds
            );
    }

    /**
     * @return array{
     *     hasBadge:bool,
     *     isHidden:bool,
     *     durationSeconds:?float
     * }
     */
    public function profileState(
        int $ownerUserId
    ): array {
        $video = $this->videoModel
            ->activeForMember(
                $ownerUserId
            );

        $duration = is_array($video)
            && is_numeric(
                $video['duration_seconds']
                    ?? null
            )
            ? (float) $video['duration_seconds']
            : null;

        return [
            'hasBadge' =>
            is_array($video),

            'isHidden' =>
            is_array($video)
                && ($video['visibility'] ?? '')
                === MemberVideoIntroductionModel::VISIBILITY_HIDDEN,

            'durationSeconds' =>
            $duration !== null
                && $duration > 0
                ? $duration
                : null,
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

    /**
     * Return every submitted video version with its moderation history.
     *
     * @return list<array<string, mixed>>
     */
    private function videoHistoryForMember(
        int $memberUserId
    ): array {
        $videos = $this->videoModel
            ->historyForMember(
                $memberUserId
            );

        if ($videos === []) {
            return [];
        }

        $videoIds = array_values(
            array_filter(
                array_map(
                    static fn(array $video): int =>
                    (int) ($video['id'] ?? 0),
                    $videos
                )
            )
        );

        $moderationHistory = [];

        if ($videoIds !== []) {
            $moderationHistory = $this->database
                ->table(
                    'member_video_moderation_history'
                )
                ->whereIn(
                    'video_introduction_id',
                    $videoIds
                )
                ->orderBy(
                    'created_at',
                    'DESC'
                )
                ->get()
                ->getResultArray();
        }

        $historyByVideo = [];

        foreach ($moderationHistory as $history) {
            $videoId = (int) (
                $history['video_introduction_id']
                ?? 0
            );

            $historyByVideo[$videoId][] = $history;
        }

        foreach ($videos as &$video) {
            $videoId = (int) (
                $video['id']
                ?? 0
            );

            $video['moderation_history'] =
                $historyByVideo[$videoId]
                ?? [];
        }

        unset($video);

        return $videos;
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
