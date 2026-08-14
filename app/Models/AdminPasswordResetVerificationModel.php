<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Handles OTP verification records used exclusively by
 * the administrator password-reset workflow.
 *
 * This model intentionally remains separate from the member
 * contact_verifications table because administrators are stored
 * in admin_users and do not use user_contacts.
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
     * Find the latest pending OTP for an administrator.
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
     * Cancel existing pending OTPs.
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
     * Count OTPs issued during a rolling period.
     */
    public function countIssuedSince(
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
            ->countAllResults();
    }

    /**
     * Find the oldest OTP in the supplied period.
     *
     * @return array<string, mixed>|null
     */
    public function findOldestIssuedSince(
        int $adminUserId,
        string $since
    ): ?array {
        $record = $this
            ->where(
                'admin_user_id',
                $adminUserId
            )
            ->where(
                'created_at >=',
                $since
            )
            ->orderBy(
                'created_at',
                'ASC'
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Atomically increment incorrect OTP attempts.
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
     * Find the most recent verified OTP.
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
     * Lock a verified OTP inside a transaction.
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
