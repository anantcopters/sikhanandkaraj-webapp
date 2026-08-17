<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores member-to-member safety reports.
 */
final class MemberProfileReportModel extends Model
{
    public const STATUS_OPEN = 'OPEN';

    public const STATUS_REVIEWED = 'REVIEWED';

    public const STATUS_DISMISSED = 'DISMISSED';

    public const STATUS_ACTION_TAKEN = 'ACTION_TAKEN';

    protected $table =
    'member_profile_reports';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $allowedFields = [
        'reporter_user_id',
        'reported_user_id',
        'description',
        'status',
        'reviewed_by_admin_id',
        'reviewed_at',
        'resolution_note',
    ];

    protected $useTimestamps =
    true;

    protected $dateFormat =
    'datetime';

    protected $createdField =
    'created_at';

    protected $updatedField =
    'updated_at';

    protected $skipValidation =
    true;

    /**
     * Determine whether the same reporter already has an open report.
     */
    public function hasOpenReport(
        int $reporterUserId,
        int $reportedUserId
    ): bool {
        return $this
            ->where(
                'reporter_user_id',
                $reporterUserId
            )
            ->where(
                'reported_user_id',
                $reportedUserId
            )
            ->where(
                'status',
                self::STATUS_OPEN
            )
            ->countAllResults() > 0;
    }

    /**
     * Prepare the administrator report queue.
     */
    public function prepareAdminListing(
        string $status,
        string $search
    ): self {
        $this->select([
            'member_profile_reports.*',

            'reporter.profile_ref_number '
                . 'AS reporter_profile_reference',

            'reporter.full_name '
                . 'AS reporter_name',

            'reported.profile_ref_number '
                . 'AS reported_profile_reference',

            'reported.full_name '
                . 'AS reported_name',

            'admin_users.full_name '
                . 'AS reviewer_name',
        ])
            ->join(
                'users reporter',
                'reporter.id = '
                    . 'member_profile_reports.reporter_user_id'
            )
            ->join(
                'users reported',
                'reported.id = '
                    . 'member_profile_reports.reported_user_id'
            )
            ->join(
                'admin_users',
                'admin_users.id = '
                    . 'member_profile_reports.reviewed_by_admin_id',
                'left'
            );

        if ($status !== 'ALL') {
            $this->where(
                'member_profile_reports.status',
                $status
            );
        }

        if ($search !== '') {
            $this
                ->groupStart()
                ->like(
                    'reporter.profile_ref_number',
                    $search,
                    'both',
                    true,
                    true
                )
                ->orLike(
                    'reported.profile_ref_number',
                    $search,
                    'both',
                    true,
                    true
                )
                ->orLike(
                    'reporter.full_name',
                    $search,
                    'both',
                    true,
                    true
                )
                ->orLike(
                    'reported.full_name',
                    $search,
                    'both',
                    true,
                    true
                )
                ->groupEnd();
        }

        return $this->orderBy(
            'member_profile_reports.created_at',
            'DESC'
        );
    }

    /**
     * Return one report for administrator review.
     *
     * @return array<string, mixed>|null
     */
    public function findForAdmin(
        int $reportId
    ): ?array {
        $record = $this
            ->select([
                'member_profile_reports.*',

                'reporter.profile_ref_number '
                    . 'AS reporter_profile_reference',

                'reporter.full_name '
                    . 'AS reporter_name',

                'reported.profile_ref_number '
                    . 'AS reported_profile_reference',

                'reported.full_name '
                    . 'AS reported_name',
            ])
            ->join(
                'users reporter',
                'reporter.id = '
                    . 'member_profile_reports.reporter_user_id'
            )
            ->join(
                'users reported',
                'reported.id = '
                    . 'member_profile_reports.reported_user_id'
            )
            ->where(
                'member_profile_reports.id',
                $reportId
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }
}
