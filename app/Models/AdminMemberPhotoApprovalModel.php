<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Provides administrator-facing member photo moderation queries.
 *
 * This model is intentionally separate from MemberPhotoModel because
 * the existing model is primarily ownership-scoped for member actions.
 */
final class AdminMemberPhotoApprovalModel extends Model
{
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $skipValidation = true;

    /**
     * Apply the pending-photo member listing query.
     *
     * The caller may invoke paginate() after this method.
     */
    public function pendingMembers(
        string $search
    ): self {
        $search = trim($search);

        $this->select(
            "
            users.id AS member_id,
            users.profile_ref_number,
            users.full_name,
            users.gender,
            users.created_at AS profile_created_at,

            EXTRACT(
                YEAR FROM AGE(
                    CURRENT_DATE,
                    member_basic_details.date_of_birth
                )
            )::INTEGER AS age,

            CONCAT_WS(
                ', ',
                NULLIF(master_cities.name, ''),
                NULLIF(master_states.name, '')
            ) AS location,

            COUNT(member_photos.id) AS pending_photo_count,

            MIN(
                member_photos.created_at
            ) AS oldest_pending_photo_at
            ",
            false
        );

        $this->join(
            'member_photos',
            "
            member_photos.member_id = users.id
            AND member_photos.status = 'PENDING'
            AND member_photos.deleted_at IS NULL
            ",
            'inner',
            false
        );

        $this->join(
            'member_basic_details',
            'member_basic_details.user_id = users.id',
            'left'
        );

        $this->join(
            'master_cities',
            'master_cities.id = member_basic_details.city_id',
            'left'
        );

        $this->join(
            'master_states',
            'master_states.id = member_basic_details.state_id',
            'left'
        );

        $this->where(
            'users.deleted_at',
            null
        );

        if ($search !== '') {
            $this->groupStart()
                ->like(
                    'LOWER(users.full_name)',
                    mb_strtolower($search),
                    'both',
                    null,
                    true
                )
                ->orLike(
                    'LOWER(users.profile_ref_number)',
                    mb_strtolower($search),
                    'both',
                    null,
                    true
                )
                ->groupEnd();
        }

        $this->groupBy([
            'users.id',
            'users.profile_ref_number',
            'users.full_name',
            'users.gender',
            'users.created_at',
            'member_basic_details.date_of_birth',
            'master_cities.name',
            'master_states.name',
        ]);

        $this->orderBy(
            'MIN(member_photos.created_at)',
            'ASC',
            false
        );

        $this->orderBy(
            'users.id',
            'ASC'
        );

        return $this;
    }

    /**
     * Return active photos for a member.
     *
     * Approved photos are included for context, but only PENDING
     * photos should expose moderation buttons in the view.
     *
     * @return list<array<string, mixed>>
     */
    public function findPhotosForMember(
        int $memberId
    ): array {
        return $this->db
            ->table('member_photos')
            ->select([
                'id',
                'member_id',
                'medium_object_key',
                'thumbnail_object_key',
                'original_object_key',
                'status',
                'visibility',
                'is_primary',
                'created_at',
            ])
            ->where(
                'member_id',
                $memberId
            )
            ->where(
                'deleted_at',
                null
            )
            ->where(
                'status !=',
                'DELETED'
            )
            ->orderBy(
                'is_primary',
                'DESC'
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * Find one pending photo without trusting a member-supplied ID.
     *
     * @return array<string, mixed>|null
     */
    public function findPendingPhoto(
        int $photoId
    ): ?array {
        $photo = $this->db
            ->table('member_photos')
            ->where(
                'id',
                $photoId
            )
            ->where(
                'status',
                'PENDING'
            )
            ->where(
                'deleted_at',
                null
            )
            ->get()
            ->getRowArray();

        return is_array($photo)
            ? $photo
            : null;
    }

    /**
     * Check whether a member still has pending photos.
     */
    public function memberHasPendingPhotos(
        int $memberId
    ): bool {
        return $this->db
            ->table('member_photos')
            ->where(
                'member_id',
                $memberId
            )
            ->where(
                'status',
                'PENDING'
            )
            ->where(
                'deleted_at',
                null
            )
            ->countAllResults() > 0;
    }
}
