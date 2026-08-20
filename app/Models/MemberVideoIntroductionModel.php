<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Model;

final class MemberVideoIntroductionModel extends Model
{
    public const STATUS_PROCESSING = 'PROCESSING';

    public const STATUS_PROCESSING_FAILED = 'PROCESSING_FAILED';

    public const STATUS_PENDING_REVIEW = 'PENDING_REVIEW';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_RESUBMISSION_REQUESTED = 'RESUBMISSION_REQUESTED';

    public const STATUS_REPLACED = 'REPLACED';

    public const STATUS_DELETED = 'DELETED';

    public const VISIBILITY_PRO = 'VISIBLE_PRO';

    public const VISIBILITY_ACCEPTED_INTEREST =
    'VISIBLE_AFTER_ACCEPTED_INTEREST';

    public const VISIBILITY_HIDDEN = 'HIDDEN';

    protected $table = 'member_video_introductions';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $useTimestamps = true;

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'public_id',
        'member_user_id',
        'version_number',
        'moderation_status',
        'visibility',
        'consent_version',
        'consented_at',
        'original_object_key',
        'playback_object_key',
        'poster_object_key',
        'source_mime_type',
        'source_size_bytes',
        'duration_seconds',
        'video_codec',
        'audio_codec',
        'width',
        'height',
        'processing_attempts',
        'processing_error',
        'processing_started_at',
        'processed_at',
        'submitted_at',
        'locked_until',
        'approved_at',
        'approved_by_admin_id',
        'rejection_reason',
        'moderated_at',
        'moderated_by_admin_id',
        'is_active',
        'hidden_at',
        'deleted_at',
        'assets_purged_at',
    ];

    public function __construct(
        ?BaseConnection $database = null
    ) {
        parent::__construct($database);
    }

    /**
     * Return the latest submitted version, including a logically deleted version.
     *
     * Deleted versions remain the authoritative latest lifecycle state. Excluding
     * them could expose or display an older replaced version after deletion.
     *
     * @return array<string, mixed>|null
     */
    public function currentForMember(
        int $memberUserId
    ): ?array {
        $row = $this
            ->where(
                'member_user_id',
                $memberUserId
            )
            ->orderBy(
                'version_number',
                'DESC'
            )
            ->first();

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function activeForMember(
        int $memberUserId
    ): ?array {
        $row = $this
            ->where(
                'member_user_id',
                $memberUserId
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'deleted_at',
                null
            )
            ->first();

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOwnedByPublicId(
        string $publicId,
        int $memberUserId
    ): ?array {
        $row = $this
            ->where(
                'public_id',
                $publicId
            )
            ->where(
                'member_user_id',
                $memberUserId
            )
            ->first();

        return is_array($row)
            ? $row
            : null;
    }

    public function nextVersionNumber(
        int $memberUserId
    ): int {
        $row = $this
            ->selectMax(
                'version_number',
                'maximum_version'
            )
            ->where(
                'member_user_id',
                $memberUserId
            )
            ->first();

        return max(
            1,
            ((int) ($row['maximum_version'] ?? 0)) + 1
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingModeration(
        int $limit = 50
    ): array {
        return $this
            ->select(
                'member_video_introductions.*, '
                    . 'users.profile_ref_number, '
                    . 'users.full_name, '
                    . 'users.gender'
            )
            ->join(
                'users',
                'users.id = member_video_introductions.member_user_id'
            )
            ->where(
                'member_video_introductions.moderation_status',
                self::STATUS_PENDING_REVIEW
            )
            ->orderBy(
                'member_video_introductions.submitted_at',
                'ASC'
            )
            ->findAll(
                max(
                    1,
                    min($limit, 100)
                )
            );
    }

    /**
     * Return every Video Introduction submitted by a member.
     *
     * Logically deleted and replaced versions are intentionally included
     * because this method is used for lifecycle history.
     *
     * @return list<array<string, mixed>>
     */
    public function historyForMember(
        int $memberUserId
    ): array {
        return $this
            ->where(
                'member_user_id',
                $memberUserId
            )
            ->orderBy(
                'version_number',
                'DESC'
            )
            ->orderBy(
                'submitted_at',
                'DESC'
            )
            ->findAll();
    }

    /**
     * Return the Video Introduction with basic member information.
     *
     * @return array<string, mixed>|null
     */
    public function findForAdminReview(
        string $publicId
    ): ?array {
        $row = $this
            ->select(
                'member_video_introductions.*, '
                    . 'users.profile_ref_number, '
                    . 'users.full_name, '
                    . 'users.gender, '
                    . 'mobile.contact_value AS mobile_number, '
                    . 'master_cities.name AS city_name, '
                    . 'master_states.name AS state_name, '
                    . 'master_countries.name AS country_name'
            )
            ->join(
                'users',
                'users.id = '
                    . 'member_video_introductions.member_user_id'
            )
            ->join(
                'user_contacts AS mobile',
                "mobile.user_id = users.id
            AND mobile.contact_type = 'MOBILE'
            AND mobile.is_primary = TRUE",
                'left',
                false
            )
            ->join(
                'member_basic_details',
                'member_basic_details.user_id = users.id',
                'left'
            )
            ->join(
                'master_cities',
                'master_cities.id = '
                    . 'member_basic_details.city_id',
                'left'
            )
            ->join(
                'master_states',
                'master_states.id = '
                    . 'member_basic_details.state_id',
                'left'
            )
            ->join(
                'master_countries',
                'master_countries.id = '
                    . 'member_basic_details.country_id',
                'left'
            )
            ->where(
                'member_video_introductions.public_id',
                trim($publicId)
            )
            ->first();

        return is_array($row)
            ? $row
            : null;
    }
}
