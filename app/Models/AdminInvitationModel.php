<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class AdminInvitationModel extends Model
{
    protected $table = 'admin_invitations';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'admin_user_id',
        'token_hash',
        'expires_at',
        'used_at',
        'revoked_at',
        'created_by',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * Return a valid, unused and non-revoked invitation.
     *
     * @return array<string, mixed>|null
     */
    public function findUsableInvitation(
        string $tokenHash
    ): ?array {
        $record = $this
            ->where('token_hash', $tokenHash)
            ->where('used_at', null)
            ->where('revoked_at', null)
            ->where(
                'expires_at >=',
                date('Y-m-d H:i:s')
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Revoke all previous unused invitations.
     */
    public function revokeForAdmin(
        int $adminUserId
    ): void {
        $this
            ->where('admin_user_id', $adminUserId)
            ->where('used_at', null)
            ->where('revoked_at', null)
            ->set([
                'revoked_at' => date('Y-m-d H:i:s'),
            ])
            ->update();
    }
}
