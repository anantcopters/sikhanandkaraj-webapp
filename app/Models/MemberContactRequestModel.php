<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores Contact Us requests created by authenticated members.
 */
final class MemberContactRequestModel extends Model
{
    public const STATUS_OPEN = 'OPEN';

    public const STATUS_RESOLVED = 'RESOLVED';

    protected $table =
    'member_contact_requests';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $allowedFields = [
        'request_reference',
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
            ->orderBy(
                'id',
                'DESC'
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Return complete support history for one authenticated member.
     *
     * @return list<array<string, mixed>>
     */
    public function historyForMember(
        int $memberUserId
    ): array {
        $records = $this
            ->select([
                'request_reference',
                'message',
                'status',
                'response_note',
                'reviewed_at',
                'created_at',
            ])
            ->where(
                'member_user_id',
                $memberUserId
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

        return is_array($records)
            ? $records
            : [];
    }

    public function referenceExists(
        string $requestReference
    ): bool {
        return $this
            ->where(
                'request_reference',
                $requestReference
            )
            ->countAllResults() > 0;
    }

    /**
     * Prepare the administrator Contact Us listing.
     */
    public function prepareAdminListing(
        string $status,
        string $search
    ): self {
        $this
            ->select([
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
                    'member_contact_requests.request_reference',
                    $search,
                    'both',
                    true,
                    true
                )
                ->orLike(
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

        return $this
            ->orderBy(
                'member_contact_requests.created_at',
                'DESC'
            )
            ->orderBy(
                'member_contact_requests.id',
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
