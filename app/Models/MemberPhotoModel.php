<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Handles member photo persistence and ownership-scoped queries.
 */
final class MemberPhotoModel extends Model
{
    protected $table = 'member_photos';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'uuid',
        'member_id',
        'media_type',
        'original_object_key',
        'medium_object_key',
        'thumbnail_object_key',
        'original_filename',
        'original_mime_type',
        'original_extension',
        'original_file_size',
        'original_width',
        'original_height',
        'status',
        'visibility',
        'is_primary',
        'uploaded_by_type',
        'uploaded_by_id',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'deleted_at',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * @return list<array<string, mixed>>
     */
    public function findActiveForMember(
        int $memberId
    ): array {
        return $this
            ->where('member_id', $memberId)
            ->where('deleted_at', null)
            ->where('status !=', 'DELETED')
            ->orderBy('is_primary', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Find the currently selected active main photo.
     *
     * @return array<string, mixed>|null
     */
    public function findPrimaryForMember(
        int $memberId
    ): ?array {
        $photo = $this
            ->where('member_id', $memberId)
            ->where('is_primary', true)
            ->where('deleted_at', null)
            ->where('status !=', 'DELETED')
            ->first();

        return is_array($photo)
            ? $photo
            : null;
    }

    /**
     * Find the earliest active member photo.
     *
     * @return array<string, mixed>|null
     */
    public function findFirstActiveForMember(
        int $memberId
    ): ?array {
        $photo = $this
            ->where('member_id', $memberId)
            ->where('deleted_at', null)
            ->where('status !=', 'DELETED')
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->first();

        return is_array($photo)
            ? $photo
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOwnedActivePhoto(
        int $photoId,
        int $memberId
    ): ?array {
        $photo = $this
            ->where('id', $photoId)
            ->where('member_id', $memberId)
            ->where('deleted_at', null)
            ->where('status !=', 'DELETED')
            ->first();

        return is_array($photo) ? $photo : null;
    }

    public function countActiveForMember(
        int $memberId
    ): int {
        return $this
            ->where('member_id', $memberId)
            ->where('deleted_at', null)
            ->where('status !=', 'DELETED')
            ->countAllResults();
    }

    public function clearPrimaryForMember(
        int $memberId,
        ?int $exceptPhotoId = null
    ): bool {
        $builder = $this
            ->builder()
            ->where('member_id', $memberId)
            ->where('deleted_at', null)
            ->where('is_primary', true);

        if ($exceptPhotoId !== null) {
            $builder->where('id !=', $exceptPhotoId);
        }

        return $builder->update([
            'is_primary' => false,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * The profile/display layer must use only approved primary photos.
     *
     * @return array<string, mixed>|null
     */
    public function findApprovedPrimaryForMember(
        int $memberId
    ): ?array {
        $photo = $this
            ->where('member_id', $memberId)
            ->where('status', 'APPROVED')
            ->where('is_primary', true)
            ->where('deleted_at', null)
            ->first();

        return is_array($photo) ? $photo : null;
    }

    /**
     * Count approved, active profile photos belonging to a member.
     */
    public function countApprovedForMember(
        int $memberId
    ): int {
        return $this
            ->where('member_id', $memberId)
            ->where('status', 'APPROVED')
            ->where('deleted_at', null)
            ->countAllResults();
    }

    /**
     * Return all approved active photos belonging to a member.
     *
     * Only columns needed for the thumbnail gallery are selected. Original
     * object keys are deliberately excluded from the initial profile query.
     *
     * @return list<array<string, mixed>>
     */
    public function findApprovedForMember(
        int $memberId
    ): array {
        return $this
            ->select([
                'id',
                'member_id',
                'thumbnail_object_key',
                'is_primary',
                'status',
                'created_at',
            ])
            ->where(
                'member_id',
                $memberId
            )
            ->where(
                'status',
                'APPROVED'
            )
            ->where(
                'deleted_at',
                null
            )
            ->orderBy(
                'is_primary',
                'DESC'
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->orderBy(
                'id',
                'DESC'
            )
            ->findAll();
    }

    /**
     * Find one approved active photo owned by a member.
     *
     * Ownership, approval and active-state checks must all pass before an
     * original signed URL can be generated.
     *
     * @return array<string, mixed>|null
     */
    public function findOwnedApprovedPhoto(
        int $photoId,
        int $memberId
    ): ?array {
        $photo = $this
            ->select([
                'id',
                'member_id',
                'original_object_key',
                'medium_object_key',
                'is_primary',
                'status',
            ])
            ->where(
                'id',
                $photoId
            )
            ->where(
                'member_id',
                $memberId
            )
            ->where(
                'status',
                'APPROVED'
            )
            ->where(
                'deleted_at',
                null
            )
            ->first();

        return is_array($photo)
            ? $photo
            : null;
    }
}
