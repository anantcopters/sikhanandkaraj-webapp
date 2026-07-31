<?php

declare(strict_types=1);

namespace App\Models\Prelaunch;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Model;

/**
 * Persistence model for locally staged pre-launch photos.
 */
final class PrelaunchPhotoModel extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $table = 'prelaunch_photos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    protected $skipValidation = true;

    protected $allowedFields = [
        'prelaunch_profile_id',
        'sequence_no',
        'original_path',
        'medium_path',
        'thumbnail_path',
        'original_filename',
        'mime_type',
        'file_extension',
        'file_size_bytes',
        'width_px',
        'height_px',
        'checksum_sha256',
        'approval_status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    public function __construct(
        ?BaseConnection $database = null
    ) {
        parent::__construct($database);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findByProfile(
        int $profileId
    ): array {
        return $this
            ->where(
                'prelaunch_profile_id',
                $profileId
            )
            ->orderBy('sequence_no', 'ASC')
            ->findAll();
    }

    public function countByProfile(
        int $profileId
    ): int {
        return $this
            ->where(
                'prelaunch_profile_id',
                $profileId
            )
            ->countAllResults();
    }
}
