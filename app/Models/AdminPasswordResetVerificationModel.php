<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores administrator password-reset OTP records.
 *
 * Administrators do not use user_contacts, therefore this table remains
 * separate from the Member contact_verifications table while following
 * the same OTP lifecycle.
 */
final class AdminPasswordResetVerificationModel extends Model
{
    public const STATUS_PENDING =
    'PENDING';

    public const STATUS_VERIFIED =
    'VERIFIED';

    public const STATUS_EXPIRED =
    'EXPIRED';

    public const STATUS_CANCELLED =
    'CANCELLED';

    public const STATUS_DELIVERY_FAILED =
    'DELIVERY_FAILED';

    protected $table =
    'admin_password_reset_verifications';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $allowedFields = [
        'admin_user_id',
        'otp_hash',
        'expires_at',
        'attempt_count',
        'resend_count',
        'status',
        'verified_at',
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
     * Find the latest pending password-reset OTP.
     *
     * @return array<string, mixed>|null
     */
    public function findLatestPending(
        int $adminUserId
    ): ?array {
        $record = $this
            ->where(
                'admin_user_id',
                $adminUserId
            )
            ->where(
                'status',
                self::STATUS_PENDING
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
     * Find the latest verified password-reset OTP.
     *
     * @return array<string, mixed>|null
     */
    public function findLatestVerified(
        int $adminUserId
    ): ?array {
        $record = $this
            ->where(
                'admin_user_id',
                $adminUserId
            )
            ->where(
                'status',
                self::STATUS_VERIFIED
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
     * Cancel all pending OTPs for an administrator.
     */
    public function cancelPending(
        int $adminUserId
    ): bool {
        return $this
            ->where(
                'admin_user_id',
                $adminUserId
            )
            ->where(
                'status',
                self::STATUS_PENDING
            )
            ->set([
                'status' =>
                self::STATUS_CANCELLED,
            ])
            ->update();
    }

    /**
     * Count OTPs which were delivered or may still be usable.
     *
     * DELIVERY_FAILED records are intentionally excluded, matching
     * the Member password-reset implementation. Provider failures
     * must not exhaust the administrator's daily OTP allowance.
     */
    public function countDeliveredOrPendingSince(
        int $adminUserId,
        string $since
    ): int {
        return $this
            ->where(
                'admin_user_id',
                $adminUserId
            )
            ->where(
                'created_at >=',
                $since
            )
            ->whereIn(
                'status',
                [
                    self::STATUS_PENDING,
                    self::STATUS_VERIFIED,
                    self::STATUS_EXPIRED,
                    self::STATUS_CANCELLED,
                ]
            )
            ->countAllResults();
    }

    /**
     * Increment incorrect verification attempts atomically.
     */
    public function incrementAttemptCount(
        int $verificationId
    ): bool {
        return $this
            ->where(
                'id',
                $verificationId
            )
            ->set(
                'attempt_count',
                'attempt_count + 1',
                false
            )
            ->update();
    }

    /**
     * Mark an OTP as expired.
     */
    public function markExpired(
        int $verificationId
    ): bool {
        return $this->update(
            $verificationId,
            [
                'status' =>
                self::STATUS_EXPIRED,
            ]
        );
    }

    /**
     * Lock a verified OTP inside an active transaction.
     *
     * @return array<string, mixed>|null
     */
    public function lockVerified(
        int $verificationId,
        int $adminUserId
    ): ?array {
        $query = $this->db->query(
            <<<'SQL'
            SELECT *
            FROM admin_password_reset_verifications
            WHERE id = ?
              AND admin_user_id = ?
              AND status = ?
            FOR UPDATE
            SQL,
            [
                $verificationId,
                $adminUserId,
                self::STATUS_VERIFIED,
            ]
        );

        $record =
            $query->getRowArray();

        return is_array($record)
            ? $record
            : null;
    }
}
