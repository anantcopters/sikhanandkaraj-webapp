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

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_VERIFIED = 'VERIFIED';

    public const STATUS_EXPIRED = 'EXPIRED';

    public const STATUS_CANCELLED = 'CANCELLED';

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
     * Cancel old pending OTPs before issuing another OTP.
     */
    public function cancelPendingForContact(
        int $userContactId,
        string $purpose
    ): void {
        $this
            ->where('user_contact_id', $userContactId)
            ->where('purpose', $purpose)
            ->where('status', self::STATUS_PENDING)
            ->set([
                'status' => self::STATUS_CANCELLED,
            ])
            ->update();
    }
}