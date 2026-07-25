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
}
