<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Provides administrator-facing member photo moderation queries.
 */
final class AdminMemberPhotoApprovalModel extends Model
{
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $skipValidation = true;

    /**
     * Apply the member list query for members having pending photos.
     */
    public function pendingMembers(
        string $search
    ): self {
        $search = mb_strtolower(
            trim($search)
        );

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

            COUNT(member_photos.id)::INTEGER
                AS pending_photo_count,

            MIN(member_photos.created_at)
                AS oldest_pending_photo_at
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

        /*
         * The latest schema uses user_id in member_basic_details.
         */
        $this->join(
            'member_basic_details',
            'member_basic_details.user_id = users.id',
            'left'
        );

        $this->join(
            'master_cities',
            'master_cities.id = '
                . 'member_basic_details.city_id',
            'left'
        );

        $this->join(
            'master_states',
            'master_states.id = '
                . 'member_basic_details.state_id',
            'left'
        );

        $this->where(
            'users.deleted_at',
            null
        );

        if ($search !== '') {
            $escapedSearch = $this->db
                ->escapeLikeString($search);

            $this->where(
                "
                (
                    LOWER(users.full_name)
                        LIKE '%{$escapedSearch}%'
                    OR LOWER(users.profile_ref_number)
                        LIKE '%{$escapedSearch}%'
                )
                ",
                null,
                false
            );
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
     * Return basic member information used in the AJAX modal.
     *
     * @return array<string, mixed>|null
     */
    public function findMemberSummary(
        int $memberId
    ): ?array {
        $member = $this->db
            ->table('users')
            ->select(
                "
                users.id AS member_id,
                users.profile_ref_number,
                users.full_name,
                users.gender,

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
                ) AS location
                ",
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
            ->where(
                'users.id',
                $memberId
            )
            ->where(
                'users.deleted_at',
                null
            )
            ->get()
            ->getRowArray();

        return is_array($member)
            ? $member
            : null;
    }

    /**
     * Return active member photos for moderation.
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
                'uuid',
                'medium_object_key',
                'original_object_key',
                'thumbnail_object_key',
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
                "
                CASE
                    WHEN status = 'PENDING' THEN 0
                    WHEN status = 'APPROVED' THEN 1
                    ELSE 2
                END
                ",
                '',
                false
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
     * Find a pending photo.
     *
     * @return array<string, mixed>|null
     */
    public function findPendingPhoto(
        int $photoId
    ): ?array {
        $photo = $this->db
            ->table('member_photos')
            ->select([
                'id',
                'member_id',
                'status',
                'is_primary',
            ])
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
     * Return pending photo IDs for a member.
     *
     * @return list<int>
     */
    public function pendingPhotoIdsForMember(
        int $memberId
    ): array {
        $rows = $this->db
            ->table('member_photos')
            ->select('id')
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
            ->orderBy(
                'id',
                'ASC'
            )
            ->get()
            ->getResultArray();

        return array_values(
            array_map(
                static fn(array $row): int =>
                (int) ($row['id'] ?? 0),
                $rows
            )
        );
    }
}
