<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Handles OTP verification records.
 */
final class ContactVerificationModel extends Model
{
    public const PURPOSE_REGISTER = 'REGISTER';

    public const PURPOSE_PASSWORD_RESET = 'PASSWORD_RESET';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_VERIFIED = 'VERIFIED';

    public const STATUS_EXPIRED = 'EXPIRED';

    public const STATUS_CANCELLED = 'CANCELLED';

    /**
     * Passwordless member login using a verified mobile number.
     */
    public const PURPOSE_LOGIN = 'LOGIN';

    /**
     * OTP record created successfully but delivery failed.
     *
     * Delivery-failed records remain useful for auditing but must not be treated
     * as pending or usable verification records.
     */
    public const STATUS_DELIVERY_FAILED = 'DELIVERY_FAILED';

    protected $table = 'contact_verifications';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'user_contact_id',
        'purpose',
        'otp_hash',
        'expires_at',
        'attempt_count',
        'resend_count',
        'status',
        'verified_at',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * Find the newest pending registration OTP for a contact.
     *
     * @return array<string, mixed>|null
     */
    public function findLatestPendingForContact(
        int $userContactId,
        string $purpose
    ): ?array {
        $record = $this
            ->where('user_contact_id', $userContactId)
            ->where('purpose', $purpose)
            ->where('status', self::STATUS_PENDING)
            ->orderBy('id', 'DESC')
            ->first();

        return is_array($record) ? $record : null;
    }

    /**
     * Cancel old pending OTPs before issuing another OTP.
     */
    public function cancelPendingForContact(
        int $userContactId,
        string $purpose
    ): bool {
        return $this
            ->where('user_contact_id', $userContactId)
            ->where('purpose', $purpose)
            ->where('status', self::STATUS_PENDING)
            ->set([
                'status' => self::STATUS_CANCELLED,
            ])
            ->update();
    }

    /**
     * Count OTP records issued during a rolling time window.
     *
     * All records are counted, including CANCELLED, EXPIRED and VERIFIED,
     * because an OTP message was still issued for each record.
     */
    public function countIssuedSince(
        int $userContactId,
        string $purpose,
        string $since
    ): int {
        return $this
            ->where('user_contact_id', $userContactId)
            ->where('purpose', $purpose)
            ->where('created_at >=', $since)
            ->countAllResults();
    }

    /**
     * Find the oldest OTP issued during a rolling time window.
     *
     * This determines when the 24-hour restriction ends.
     *
     * @return array<string, mixed>|null
     */
    public function findOldestIssuedSince(
        int $userContactId,
        string $purpose,
        string $since
    ): ?array {
        $record = $this
            ->where('user_contact_id', $userContactId)
            ->where('purpose', $purpose)
            ->where('created_at >=', $since)
            ->orderBy('created_at', 'ASC')
            ->first();

        return is_array($record) ? $record : null;
    }

    /**
     * Increment incorrect verification attempts atomically.
     */
    public function incrementAttemptCount(int $verificationId): bool
    {
        return $this
            ->where('id', $verificationId)
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
    public function markExpired(int $verificationId): bool
    {
        return $this->update($verificationId, [
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Count OTP records that were delivered or are still awaiting delivery result.
     *
     * DELIVERY_FAILED records are excluded because no usable OTP reached the
     * member. This prevents provider outages from exhausting the member's quota.
     */
    public function countDeliveredOrPendingSince(
        int $userContactId,
        string $purpose,
        string $since
    ): int {
        return $this
            ->where(
                'user_contact_id',
                $userContactId
            )
            ->where(
                'purpose',
                $purpose
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
     * Lock and return a verified password-reset authorization.
     *
     * Must be called inside an active transaction.
     *
     * @return array<string, mixed>|null
     */
    public function lockVerifiedPasswordReset(
        int $verificationId,
        int $userContactId
    ): ?array {
        $query = $this->db->query(
            <<<'SQL'
            SELECT *
            FROM contact_verifications
            WHERE id = ?
              AND user_contact_id = ?
              AND purpose = ?
              AND status = ?
            FOR UPDATE
        SQL,
            [
                $verificationId,
                $userContactId,
                self::PURPOSE_PASSWORD_RESET,
                self::STATUS_VERIFIED,
            ]
        );

        $record = $query->getRowArray();

        return is_array($record)
            ? $record
            : null;
    }
}
