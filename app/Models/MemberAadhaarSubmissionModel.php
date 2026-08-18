<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Persists immutable member Aadhaar upload/review history.
 */
final class MemberAadhaarSubmissionModel extends Model
{
    public const STATUS_UNDER_REVIEW = 'UNDER_REVIEW';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    protected $table = 'member_aadhaar_submissions';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'upload_reference',
        'member_id',
        'object_key',
        'mime_type',
        'file_extension',
        'file_size_bytes',
        'checksum_sha256',
        'status',
        /*
        * Separate Aadhaar verification identity.
        */
        'aadhaar_name',
        'aadhaar_date_of_birth',

        'rejection_reason',
        'reviewed_by_admin_id',
        'reviewed_at',
        'uploaded_at',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * Return the newest submission for a member.
     *
     * @return array<string, mixed>|null
     */
    public function latestForMember(int $memberId): ?array
    {
        $row = $this
            ->where('member_id', $memberId)
            ->orderBy('uploaded_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        return is_array($row) ? $row : null;
    }

    /**
     * Return only the approved Aadhaar identity values required by
     * the shared read-only profile view.
     *
     * Document location, checksum, upload reference, reviewer,
     * timestamps and rejection history are intentionally excluded.
     *
     * @return array{
     *     aadhaar_name:string,
     *     aadhaar_date_of_birth:string
     * }|null
     */
    public function approvedIdentityForMember(
        int $memberId
    ): ?array {
        if ($memberId <= 0) {
            return null;
        }

        $row = $this
            ->select([
                'aadhaar_name',
                'aadhaar_date_of_birth',
            ])
            ->where(
                'member_id',
                $memberId
            )
            ->where(
                'status',
                self::STATUS_APPROVED
            )
            ->first();

        if (!is_array($row)) {
            return null;
        }

        $aadhaarName = preg_replace(
            '/\s+/u',
            ' ',
            trim(
                (string) (
                    $row['aadhaar_name']
                    ?? ''
                )
            )
        ) ?? '';

        $aadhaarDateOfBirth = trim(
            (string) (
                $row['aadhaar_date_of_birth']
                ?? ''
            )
        );

        /*
     * An approved record should always contain both fields because
     * the database constraint requires them. Fail closed if an old
     * or manually-created inconsistent record exists.
     */
        if (
            $aadhaarName === ''
            || preg_match(
                '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
                $aadhaarDateOfBirth
            ) !== 1
        ) {
            return null;
        }

        return [
            'aadhaar_name' =>
            $aadhaarName,

            'aadhaar_date_of_birth' =>
            $aadhaarDateOfBirth,
        ];
    }

    /**
     * Build the paginated administrator pending-review query.
     */
    public function preparePendingListing(string $search): self
    {
        $this->select(
            "
            member_aadhaar_submissions.id AS submission_id,
            member_aadhaar_submissions.uploaded_at,
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
        );

        $this->join('users', 'users.id = member_aadhaar_submissions.member_id');
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
            'member_aadhaar_submissions.status',
            self::STATUS_UNDER_REVIEW
        );
        $this->where('users.deleted_at', null);

        $normalizedSearch = mb_strtolower(trim($search));

        if ($normalizedSearch !== '') {
            $escaped = $this->db->escapeLikeString($normalizedSearch);

            $this->where(
                "(
                    LOWER(users.full_name) LIKE '%{$escaped}%'
                    OR LOWER(users.profile_ref_number) LIKE '%{$escaped}%'
                )",
                null,
                false
            );
        }

        return $this
            ->orderBy('member_aadhaar_submissions.uploaded_at', 'ASC')
            ->orderBy('member_aadhaar_submissions.id', 'ASC');
    }

    /**
     * Find the pending submission and member summary by public reference.
     *
     * @return array<string, mixed>|null
     */
    public function pendingReviewByProfileReference(
        string $reference
    ): ?array {
        $row = $this
            ->select(
                "
            member_aadhaar_submissions.*,
            users.profile_ref_number,
            users.full_name,
            users.gender,
            member_basic_details.date_of_birth,
            CONCAT_WS(
                ', ',
                NULLIF(master_cities.name, ''),
                NULLIF(master_states.name, '')
            ) AS location
            ",
                false
            )
            ->join(
                'users',
                'users.id = member_aadhaar_submissions.member_id'
            )
            ->join(
                'member_basic_details',
                'member_basic_details.user_id = users.id',
                'left'
            )
            ->join(
                'master_cities',
                'master_cities.id = member_basic_details.city_id',
                'left'
            )
            ->join(
                'master_states',
                'master_states.id = member_basic_details.state_id',
                'left'
            )
            ->where(
                'users.profile_ref_number',
                strtoupper(trim($reference))
            )
            ->where(
                'users.deleted_at',
                null
            )
            ->where(
                'member_aadhaar_submissions.status',
                self::STATUS_UNDER_REVIEW
            )
            ->first();

        return is_array($row) ? $row : null;
    }

    /**
     * Lock the current pending submission for a review decision.
     *
     * @return array<string, mixed>|null
     */
    public function lockPendingByProfileReference(string $reference): ?array
    {
        $row = $this->db->query(
            <<<'SQL'
            SELECT
                s.*,
                u.profile_ref_number,
                u.full_name
            FROM member_aadhaar_submissions AS s
            INNER JOIN users AS u ON u.id = s.member_id
            WHERE u.profile_ref_number = ?
              AND u.deleted_at IS NULL
              AND s.status = 'UNDER_REVIEW'
            FOR UPDATE OF s, u
            SQL,
            [strtoupper(trim($reference))]
        )->getRowArray();

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function historyForMember(int $memberId): array
    {
        return $this
            ->select([
                'member_aadhaar_submissions.status',
                'member_aadhaar_submissions.uploaded_at',
                'member_aadhaar_submissions.reviewed_at',
                'member_aadhaar_submissions.rejection_reason',
                'admin_users.full_name AS reviewer_name',
            ])
            ->join(
                'admin_users',
                'admin_users.id = member_aadhaar_submissions.reviewed_by_admin_id',
                'left'
            )
            ->where('member_aadhaar_submissions.member_id', $memberId)
            ->orderBy('member_aadhaar_submissions.uploaded_at', 'DESC')
            ->orderBy('member_aadhaar_submissions.id', 'DESC')
            ->findAll();
    }
}
