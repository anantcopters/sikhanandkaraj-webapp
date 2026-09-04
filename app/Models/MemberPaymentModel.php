<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MemberPaymentModel extends Model
{
    public const STATUS_CREATED =
    'CREATED';

    public const STATUS_PAID =
    'PAID';

    public const STATUS_PROCESSED =
    'PROCESSED';

    public const STATUS_FAILED =
    'FAILED';

    public const PROVIDER_DEVELOPMENT =
    'DEVELOPMENT_SIMULATOR';

    public const PROVIDER_OFFLINE =
    'OFFLINE';

    public const PAYMENT_METHOD_BANK_TRANSFER =
    'BANK_TRANSFER';

    public const PAYMENT_METHOD_UPI =
    'UPI';

    public const PAYMENT_METHOD_CASH =
    'CASH';

    public const PAYMENT_METHOD_OTHER =
    'OTHER';

    protected $table =
    'member_payments';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $allowedFields = [
        'user_id',
        'membership_plan_id',
        'member_membership_id',
        'transaction_reference',
        'provider',
        'provider_order_id',
        'provider_payment_id',
        'provider_event_id',
        'status',
        'plan_code_snapshot',
        'plan_name_snapshot',
        'amount_paise',
        'currency',
        'purchase_action',
        'payment_method',
        'recorded_by_admin_user_id',
        'payment_note',
        'provider_response',
        'paid_at',
        'processed_at',
        'coupon_id',
        'plan_price_paise',
        'coupon_discount_paise',
        'final_payable_paise'
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
     * Lock one payment while processing a provider callback.
     *
     * This is the application-level idempotency boundary.
     *
     * Must be called inside a transaction.
     *
     * @return array<string, mixed>|null
     */
    public function lockByReference(
        string $transactionReference
    ): ?array {
        $transactionReference =
            trim(
                $transactionReference
            );

        if ($transactionReference === '') {
            return null;
        }

        $record =
            $this->db
            ->query(
                <<<'SQL'
                    SELECT *
                    FROM member_payments
                    WHERE transaction_reference = ?
                    LIMIT 1
                    FOR UPDATE
                    SQL,
                [
                    $transactionReference,
                ]
            )
            ->getRowArray();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Return a payment belonging to one member.
     *
     * Used by the success page. The member ID is always taken from the
     * authenticated session, never from the URL.
     *
     * @return array<string, mixed>|null
     */
    public function findForUserByReference(
        int $userId,
        string $transactionReference
    ): ?array {
        if ($userId <= 0) {
            return null;
        }

        $transactionReference =
            trim(
                $transactionReference
            );

        if ($transactionReference === '') {
            return null;
        }

        $record =
            $this
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'transaction_reference',
                $transactionReference
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }
}
