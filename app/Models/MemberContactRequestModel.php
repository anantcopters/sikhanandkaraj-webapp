<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores authenticated member Contact Us requests.
 */
final class MemberContactRequestModel extends Model
{
    public const STATUS_OPEN = 'OPEN';

    public const STATUS_IN_PROGRESS =
    'IN_PROGRESS';

    public const STATUS_RESOLVED =
    'RESOLVED';

    public const STATUS_CLOSED =
    'CLOSED';

    protected $table =
    'member_contact_requests';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $allowedFields = [
        'member_user_id',
        'message',
        'status',
        'reviewed_by_admin_id',
        'reviewed_at',
        'response_note',
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
     * Return the member's most recent request.
     *
     * @return array<string, mixed>|null
     */
    public function latestForMember(
        int $memberUserId
    ): ?array {
        $record = $this
            ->where(
                'member_user_id',
                $memberUserId
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Prepare the administrator Contact Us queue.
     */
    public function prepareAdminListing(
        string $status,
        string $search
    ): self {
        $this->select([
            'member_contact_requests.*',

            'users.profile_ref_number',

            'users.full_name AS member_name',

            'admin_users.full_name '
                . 'AS reviewer_name',
        ])
            ->join(
                'users',
                'users.id = '
                    . 'member_contact_requests.member_user_id'
            )
            ->join(
                'admin_users',
                'admin_users.id = '
                    . 'member_contact_requests.reviewed_by_admin_id',
                'left'
            );

        if ($status !== 'ALL') {
            $this->where(
                'member_contact_requests.status',
                $status
            );
        }

        if ($search !== '') {
            $this
                ->groupStart()
                ->like(
                    'users.profile_ref_number',
                    $search,
                    'both',
                    true,
                    true
                )
                ->orLike(
                    'users.full_name',
                    $search,
                    'both',
                    true,
                    true
                )
                ->groupEnd();
        }

        return $this->orderBy(
            'member_contact_requests.created_at',
            'DESC'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForAdmin(
        int $requestId
    ): ?array {
        $record = $this
            ->select([
                'member_contact_requests.*',

                'users.profile_ref_number',

                'users.full_name AS member_name',
            ])
            ->join(
                'users',
                'users.id = '
                    . 'member_contact_requests.member_user_id'
            )
            ->where(
                'member_contact_requests.id',
                $requestId
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }
}
