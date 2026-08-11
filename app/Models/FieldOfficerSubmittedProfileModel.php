<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Model;

final class FieldOfficerSubmittedProfileModel
extends Model
{
    protected $table =
    'vw_field_officer_submitted_profiles';

    protected $primaryKey =
    'row_key';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    false;

    protected $useTimestamps =
    false;

    protected $skipValidation =
    true;

    public function __construct(
        ?BaseConnection $database = null
    ) {
        parent::__construct(
            $database
        );
    }

    public function prepareListing(
        int $fieldOfficerId,
        string $status,
        string $search
    ): self {
        $status = strtoupper(
            trim($status)
        );

        if (
            !in_array(
                $status,
                [
                    'ALL',
                    'DRAFT',
                    'APPROVED',
                ],
                true
            )
        ) {
            $status = 'ALL';
        }

        $search = preg_replace(
            '/\s+/u',
            ' ',
            trim($search)
        ) ?? '';

        $search = mb_substr(
            $search,
            0,
            100
        );

        $this->where(
            'field_officer_id',
            $fieldOfficerId
        );

        if ($status !== 'ALL') {
            $this->where(
                'display_status',
                $status
            );
        }

        if ($search !== '') {
            $escaped =
                $this->db
                ->escapeLikeString(
                    $search
                );

            $pattern =
                $this->db->escape(
                    '%' . $escaped . '%'
                );

            $this
                ->groupStart()
                ->where(
                    'profile_reference ILIKE '
                        . $pattern,
                    null,
                    false
                )
                ->orWhere(
                    'full_name ILIKE '
                        . $pattern,
                    null,
                    false
                )
                ->orWhere(
                    'COALESCE(mobile_number, \'\') '
                        . 'ILIKE '
                        . $pattern,
                    null,
                    false
                )
                ->orWhere(
                    'COALESCE(city_name, \'\') '
                        . 'ILIKE '
                        . $pattern,
                    null,
                    false
                )
                ->orWhere(
                    'COALESCE(state_name, \'\') '
                        . 'ILIKE '
                        . $pattern,
                    null,
                    false
                )
                ->groupEnd();
        }

        return $this->orderBy(
            'submitted_at',
            'DESC'
        );
    }

    public function countForOfficer(
        int $fieldOfficerId
    ): int {
        if ($fieldOfficerId <= 0) {
            return 0;
        }

        return $this
            ->where(
                'field_officer_id',
                $fieldOfficerId
            )
            ->countAllResults();
    }

    /**
     * Member access may come from either:
     *
     * 1. member_family_details;
     * 2. historical/migrated prelaunch ownership.
     */
    public function memberBelongsToOfficer(
        int $memberId,
        int $fieldOfficerId
    ): bool {
        if (
            $memberId <= 0
            || $fieldOfficerId <= 0
        ) {
            return false;
        }

        $familyMatch =
            $this->db
            ->table(
                'member_family_details'
            )
            ->where(
                'user_id',
                $memberId
            )
            ->where(
                'field_officer_id',
                $fieldOfficerId
            )
            ->countAllResults()
            > 0;

        if ($familyMatch) {
            return true;
        }

        return $this->db
            ->table(
                'prelaunch_profiles'
            )
            ->where(
                'migrated_user_id',
                $memberId
            )
            ->where(
                'field_officer_id',
                $fieldOfficerId
            )
            ->where(
                'deleted_at',
                null
            )
            ->countAllResults() > 0;
    }
}
