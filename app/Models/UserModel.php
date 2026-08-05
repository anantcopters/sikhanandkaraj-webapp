<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/**
 * Handles user account persistence.
 */
final class UserModel extends Model
{
    /**
     * Registration or mobile verification is incomplete.
     */
    public const STATUS_PENDING = 'PENDING';

    /**
     * Account is verified and available for member login.
     */
    public const STATUS_ACTIVE = 'ACTIVE';

    /**
     * Account has been blocked by an administrator.
     */
    public const STATUS_SUSPENDED = 'SUSPENDED';

    /**
     * Account has been logically deleted or disabled.
     */
    public const STATUS_DELETED = 'DELETED';

    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'prelaunch_profile_id',
        'profile_ref_number',
        'profile_created_for',
        'gender',
        'full_name',
        'password_hash',
        'account_status',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $deletedField = 'deleted_at';

    protected $skipValidation = true;

    /**
     * Check whether a profile reference already exists.
     */
    public function profileReferenceExists(
        string $reference
    ): bool {
        return $this
            ->where(
                'profile_ref_number',
                $reference
            )
            ->countAllResults() > 0;
    }

    /**
     * Find the member created from a prelaunch profile.
     *
     * @return array<string, mixed>|null
     */
    public function findByPrelaunchProfileId(
        int $prelaunchProfileId
    ): ?array {
        if ($prelaunchProfileId <= 0) {
            return null;
        }

        $record = $this
            ->where(
                'prelaunch_profile_id',
                $prelaunchProfileId
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Prepare the administrator member-list query.
     *
     * The method intentionally returns the model so the existing CI4
     * paginate() flow and shared Pagination component can be reused.
     */
    public function prepareAdminMemberListing(
        string $status = 'ALL',
        string $search = ''
    ): self {
        $this->select([
            'users.id',
            'users.profile_ref_number',
            'users.profile_created_for',
            'users.gender',
            'users.full_name',
            'users.account_status',
            'users.created_at',

            'mobile.contact_value AS mobile_number',
            'mobile.normalized_value AS normalized_mobile',
            'mobile.is_verified AS is_mobile_verified',

            'email.contact_value AS email_address',
            'email.is_verified AS is_email_verified',

            'member_basic_details.date_of_birth',

            'master_cities.name AS city_name',
            'master_states.name AS state_name',
            'master_countries.name AS country_name',
        ]);

        $this->join(
            'user_contacts AS mobile',
            "mobile.user_id = users.id
            AND mobile.contact_type = 'MOBILE'
            AND mobile.is_primary = TRUE",
            'left',
            false
        );

        $this->join(
            'user_contacts AS email',
            "email.user_id = users.id
            AND email.contact_type = 'EMAIL'
            AND email.is_primary = TRUE",
            'left',
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

        $this->join(
            'master_countries',
            'master_countries.id = member_basic_details.country_id',
            'left'
        );

        $normalizedStatus = mb_strtoupper(
            trim($status)
        );

        /*
     * DELETED is a logical account status, not necessarily a CI4 soft-delete
     * row. Normal screens exclude soft-deleted rows completely.
     */
        $this->where(
            'users.deleted_at',
            null
        );

        if (
            in_array(
                $normalizedStatus,
                [
                    self::STATUS_PENDING,
                    self::STATUS_ACTIVE,
                    self::STATUS_SUSPENDED,
                    self::STATUS_DELETED,
                ],
                true
            )
        ) {
            $this->where(
                'users.account_status',
                $normalizedStatus
            );
        }

        $this->applyAdminMemberSearch(
            $search
        );

        $this->orderBy(
            'users.created_at',
            'DESC'
        );

        $this->orderBy(
            'users.id',
            'DESC'
        );

        return $this;
    }

    /**
     * Find an administrator-visible member.
     *
     * @return array<string, mixed>|null
     */
    public function findForAdmin(
        int $userId
    ): ?array {
        if ($userId <= 0) {
            return null;
        }

        $record = $this
            ->select([
                'users.id',
                'users.profile_ref_number',
                'users.profile_created_for',
                'users.gender',
                'users.full_name',
                'users.account_status',
                'users.created_at',
                'users.updated_at',

                'mobile.contact_value AS mobile_number',
                'mobile.is_verified AS is_mobile_verified',

                'email.contact_value AS email_address',
                'email.is_verified AS is_email_verified',
            ])
            ->join(
                'user_contacts AS mobile',
                "mobile.user_id = users.id
                    AND mobile.contact_type = 'MOBILE'
                    AND mobile.is_primary = TRUE",
                'left',
                false
            )
            ->join(
                'user_contacts AS email',
                "email.user_id = users.id
                    AND email.contact_type = 'EMAIL'
                    AND email.is_primary = TRUE",
                'left',
                false
            )
            ->where(
                'users.id',
                $userId
            )
            ->where(
                'users.deleted_at',
                null
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Lock one member account before a status transition.
     *
     * @return array<string, mixed>|null
     */
    public function findForStatusUpdate(
        int $userId
    ): ?array {
        if ($userId <= 0) {
            return null;
        }

        $record = $this->db
            ->query(
                <<<'SQL'
                SELECT
                    id,
                    profile_ref_number,
                    full_name,
                    account_status,
                    deleted_at
                FROM users
                WHERE id = ?
                  AND deleted_at IS NULL
                FOR UPDATE
                SQL,
                [$userId]
            )
            ->getRowArray();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Apply a bounded, escaped administrator search.
     */
    private function applyAdminMemberSearch(
        string $search
    ): void {
        $normalizedSearch = preg_replace(
            '/\s+/u',
            ' ',
            trim($search)
        ) ?? '';

        $normalizedSearch = mb_substr(
            $normalizedSearch,
            0,
            100
        );

        if ($normalizedSearch === '') {
            return;
        }

        $escapedSearch = $this->db
            ->escapeLikeString(
                mb_strtolower(
                    $normalizedSearch
                )
            );

        $quotedPattern = $this->db->escape(
            '%' . $escapedSearch . '%'
        );

        $this
            ->groupStart()
            ->where(
                'LOWER(users.profile_ref_number) '
                    . 'LIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'LOWER(users.full_name) '
                    . 'LIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'LOWER(COALESCE(mobile.contact_value, \'\')) '
                    . 'LIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'LOWER(COALESCE(mobile.normalized_value, \'\')) '
                    . 'LIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'LOWER(COALESCE(email.contact_value, \'\')) '
                    . 'LIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'LOWER(COALESCE(master_cities.name, \'\')) '
                    . 'LIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'LOWER(COALESCE(master_states.name, \'\')) '
                    . 'LIKE ' . $quotedPattern,
                null,
                false
            )
            ->orWhere(
                'LOWER(COALESCE(master_countries.name, \'\')) '
                    . 'LIKE ' . $quotedPattern,
                null,
                false
            )
            ->groupEnd();
    }
}
