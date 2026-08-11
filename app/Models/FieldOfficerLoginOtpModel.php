<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Model;

final class FieldOfficerLoginOtpModel extends Model
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
    'field_officer_login_otps';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'field_officer_id',
        'mobile_number',
        'otp_hash',
        'expires_at',
        'attempt_count',
        'status',
        'verified_at',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    public function __construct(
        ?BaseConnection $database = null
    ) {
        parent::__construct(
            $database
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLatestPending(
        int $fieldOfficerId
    ): ?array {
        if ($fieldOfficerId <= 0) {
            return null;
        }

        $record = $this
            ->where(
                'field_officer_id',
                $fieldOfficerId
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

    public function cancelPending(
        int $fieldOfficerId
    ): bool {
        if ($fieldOfficerId <= 0) {
            return false;
        }

        return $this
            ->where(
                'field_officer_id',
                $fieldOfficerId
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

    public function incrementAttemptCount(
        int $otpId
    ): bool {
        return $this
            ->where(
                'id',
                $otpId
            )
            ->set(
                'attempt_count',
                'attempt_count + 1',
                false
            )
            ->update();
    }

    public function countDeliveredSince(
        int $fieldOfficerId,
        string $since
    ): int {
        return $this
            ->where(
                'field_officer_id',
                $fieldOfficerId
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
}
